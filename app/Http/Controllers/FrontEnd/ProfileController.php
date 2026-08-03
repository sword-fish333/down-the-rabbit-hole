<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Concept;
use App\Models\Conversation;
use App\Models\User;
use App\Models\XpEvent;
use App\Services\ValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The learner's own account: details, password, avatar — plus the learning
 * record that makes the page worth visiting (mastery, descent, subjects).
 */
class ProfileController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('frontend.profile.index', [
            'user' => $user,
            'streak' => $user->streak,
            'record' => $this->record($user),
            // The one stat worth quoting out loud: "7 layers deep on Stoicism".
            'deepestDive' => $user->conversations()
                ->orderByDesc('current_depth')
                ->latest('updated_at')
                ->first(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        session()->flash('active_profile_tab', 'profile');

        $validated = $request->validate([
            'first_name' => 'required|string|min:2|max:60',
            'last_name' => 'nullable|string|max:60',
            'phone' => 'nullable|string|max:20',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(auth()->id())],
        ]);

        $user = auth()->user();

        // A changed address is unverified again — and the old signed link,
        // which hashes the previous address, stops working automatically.
        if ($validated['email'] !== $user->email) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        $user->update($validated);

        return back()->with('success', __('frontend.profile.updated'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        session()->flash('active_profile_tab', 'password');

        $validated = $request->validate([
            'current_password' => 'required|string|max:60',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = auth()->user();
        $check = new ValidationService;

        // OAuth-only learners have no local password to check against.
        if ($user->password && ! Hash::check($validated['current_password'], $user->password)) {
            $check->errorEncountered(__('frontend.profile.password-incorrect'));
        }

        if (! $check->isSuccessfulCheck()) {
            return back()->with('error', $check->getFirstError());
        }

        $user->update(['password' => $validated['password']]);

        return back()->with('success', __('frontend.profile.password-updated'));
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        session()->flash('active_profile_tab', 'profile');

        $request->validate([
            'profile_img' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = auth()->user();

        try {
            deleteFile($user->profile_image, User::IMAGES_DIRECTORY);
            $user->update([
                'profile_image' => saveFileToStorage($request->file('profile_img'), User::IMAGES_DIRECTORY),
            ]);

            return back()->with('success', __('frontend.profile.avatar-updated'));
        } catch (\Throwable $e) {
            fullLog($e);

            return back()->with('error', __('frontend.profile.error'));
        }
    }

    /**
     * What the learner actually earned. Depth and mastery, never time on site.
     *
     * @return array<string, int>
     */
    private function record(User $user): array
    {
        $subjectIds = $user->conversations()->pluck('id');

        return [
            'subjects' => $subjectIds->count(),
            'surfaced' => $user->conversations()->where('status', Conversation::STATUS_SURFACED)->count(),
            'deepest' => (int) $user->conversations()->max('current_depth'),
            'layers' => XpEvent::where('user_id', $user->id)
                ->where('type', XpEvent::TYPE_LAYER_COMPLETED)->count(),
            'mastered' => Concept::whereIn('conversation_id', $subjectIds)
                ->where('state', Concept::STATE_MASTERED)->count(),
            'to_review' => Concept::whereIn('conversation_id', $subjectIds)
                ->where('state', Concept::STATE_MISUNDERSTOOD)->count(),
        ];
    }
}
