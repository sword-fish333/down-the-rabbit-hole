<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\LearningMode;
use App\Services\Chat\ChatStreamingService;
use App\Services\Chat\DescentService;
use App\Services\Chat\MasteryService;
use App\Services\ValidationService;
use App\Traits\ValidationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The descent: start a hole, view it, stream a teaching turn, submit proof, and
 * descend. Thin — every rule lives in DescentService / ChatStreamingService.
 *
 * Two transports on purpose: teaching streams over SSE (prose, rendered as it
 * arrives) while grading answers as JSON (a structured verdict the UI unpacks
 * into criterion-by-criterion feedback).
 */
class ChatController extends Controller
{
    use ValidationHelper;

    public function __construct(
        private readonly DescentService $descent,
        private readonly ChatStreamingService $streaming,
        private readonly MasteryService $mastery,
    ) {
        $this->initializeValidator();
    }

    /** The learner's library of holes — resume, review, or start another. */
    public function index(): View
    {
        $holes = Conversation::query()
            ->ownedBy(auth()->id())
            ->with('learningMode:id,name,icon,accent')
            ->withCount(['concepts as mastered_count' => fn ($query) => $query->where('state', 'mastered')])
            ->latest('updated_at')
            ->paginate(12);

        return view('frontend.chat.index', [
            'holes' => $holes,
            'modes' => LearningMode::query()->enabled()->ordered()->get(),
        ]);
    }

    /** Start a new rabbit hole from the home composer. */
    public function descend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|min:2|max:500',
            'learning_mode_id' => ['nullable', Rule::exists('learning_modes', 'id')->where('enabled', true)],
        ]);

        $mode = LearningMode::find($validated['learning_mode_id'] ?? null);
        $conversation = $this->descent->start(auth()->user(), $validated['prompt'], $mode);

        $this->rememberGuestHole($request, $conversation);

        return redirect()->route('hole.show', $conversation)->with('stream', true);
    }

    public function show(Request $request, Conversation $conversation): View|RedirectResponse
    {
        if (! $this->canAccess($request, $conversation)) {
            return redirect()->route('home')->with('error', __('frontend.chat.no-access'));
        }

        $conversation->load('learningMode');

        return view('frontend.chat.show', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->orderBy('id')->get(),
            'attempts' => $conversation->checkpointAttempts()->orderBy('id')->get()->keyBy('message_id'),
            'concepts' => $conversation->concepts()->orderBy('first_seen_depth')->orderBy('name')->get(),
            'mastery' => $this->mastery->tally($conversation),
        ]);
    }

    /** SSE endpoint: streams the guide's teaching turn for the current layer. */
    public function stream(Request $request, Conversation $conversation): StreamedResponse
    {
        $validation = $this->validateTurn($request, $conversation);

        if (! $validation->isSuccessfulCheck()) {
            return $this->streaming->error($validation->getFirstError());
        }

        $reframe = $request->string('reframe')->value();
        $reframe = in_array($reframe, DescentService::REFRAMES, true) ? $reframe : null;

        $this->descent->recordTurn(auth()->user(), $this->guestKey($request));

        return $this->streaming->stream($conversation, $reframe);
    }

    /**
     * Submit the proof for the open checkpoint. Returns the structured verdict
     * so the workspace can render criterion-by-criterion feedback in place.
     */
    public function checkpoint(Request $request, Conversation $conversation): JsonResponse
    {
        $validation = $this->validateCheckpoint($request, $conversation);

        if (! $validation->isSuccessfulCheck()) {
            return response()->json(['message' => $validation->getFirstError()], $validation->status());
        }

        $this->descent->recordTurn(auth()->user(), $this->guestKey($request));

        $graded = $this->descent->gradeCheckpoint(
            $conversation,
            $request->string('message')->value(),
            $request->filled('self_rating') ? $request->integer('self_rating') : null,
        );

        $attempt = $graded['attempt'];

        return response()->json([
            'state' => $graded['state'],
            'attempt' => [
                'verdict' => $attempt->verdict,
                'score' => $attempt->score,
                'feedback' => $attempt->feedback,
                'criteria' => $attempt->criteria ?? [],
                'demonstrated_concepts' => $attempt->demonstrated_concepts ?? [],
                'missing_concepts' => $attempt->missing_concepts ?? [],
                'misconceptions' => $attempt->misconceptions ?? [],
                'recommended_action' => $attempt->recommended_action,
                'calibration_gap' => $attempt->calibrationGap(),
            ],
        ]);
    }

    private function validateTurn(Request $request, Conversation $conversation): ValidationService
    {
        if (! $this->canAccess($request, $conversation)) {
            return $this->errorEncountered(__('frontend.chat.no-access'), 403);
        }

        if ($conversation->isSurfaced()) {
            return $this->errorEncountered(__('frontend.chat.already-surfaced'));
        }

        if ($this->descent->dailyLimitReached(auth()->user(), $this->guestKey($request))) {
            return $this->errorEncountered(__('frontend.chat.daily-limit'), 429);
        }

        return $this->successfulCheck();
    }

    private function validateCheckpoint(Request $request, Conversation $conversation): ValidationService
    {
        $request->validate([
            'message' => 'required|string|min:2|max:4000',
            'self_rating' => 'nullable|integer|min:0|max:100',
        ]);

        if (! $conversation->isCheckpointPending()) {
            return $this->errorEncountered(__('frontend.chat.no-checkpoint'), 409);
        }

        return $this->validateTurn($request, $conversation);
    }

    /** Owned by the signed-in user, or a guest hole remembered in this session. */
    private function canAccess(Request $request, Conversation $conversation): bool
    {
        if ($conversation->user_id) {
            return $conversation->user_id === auth()->id();
        }

        return in_array($conversation->id, $request->session()->get('dth_holes', []), true);
    }

    private function rememberGuestHole(Request $request, Conversation $conversation): void
    {
        if ($conversation->user_id) {
            return;
        }

        $holes = $request->session()->get('dth_holes', []);
        $holes[] = $conversation->id;
        $request->session()->put('dth_holes', $holes);
    }

    private function guestKey(Request $request): string
    {
        return $request->session()->getId();
    }
}
