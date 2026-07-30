<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningMode;
use App\Services\Admin\LearningModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Full CRUD over the learning modes. Thin: validate → service → response.
 */
class LearningModeController extends Controller
{
    public function __construct(private readonly LearningModeService $modes) {}

    public function index(Request $request): View
    {
        $modes = LearningMode::query()
            ->withCount('conversations')
            ->when($request->filled('search'), fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('slug', 'like', '%'.$request->string('search').'%')
            ))
            ->when($request->filled('status'), fn ($query) => $query->where('enabled', $request->string('status') === 'enabled'))
            ->ordered()
            ->paginate(15)
            ->withQueryString();

        return view('admin.learning_modes.index', compact('modes'));
    }

    public function create(): View
    {
        return view('admin.learning_modes.create', ['mode' => new LearningMode]);
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->modes->create($this->validated($request));

        if (! $result->isSuccessfulCheck()) {
            return back()->withInput()->with('error', $result->getFirstError());
        }

        return redirect()->route('admin.learning-mode.index')
            ->with('success', __('admin/backend.learning-modes.created'));
    }

    public function edit(LearningMode $learning_mode): View
    {
        return view('admin.learning_modes.edit', ['mode' => $learning_mode]);
    }

    public function update(Request $request, LearningMode $learning_mode): RedirectResponse
    {
        $result = $this->modes->update($learning_mode, $this->validated($request, $learning_mode));

        if (! $result->isSuccessfulCheck()) {
            return back()->withInput()->with('error', $result->getFirstError());
        }

        return redirect()->route('admin.learning-mode.index')
            ->with('success', __('admin/backend.learning-modes.updated'));
    }

    public function destroy(LearningMode $learning_mode): RedirectResponse
    {
        $result = $this->modes->delete($learning_mode);

        if (! $result->isSuccessfulCheck()) {
            return back()->with('error', $result->getFirstError());
        }

        return redirect()->route('admin.learning-mode.index')
            ->with('success', __('admin/backend.learning-modes.deleted'));
    }

    public function toggle(LearningMode $learning_mode): RedirectResponse
    {
        $result = $this->modes->toggle($learning_mode);

        if (! $result->isSuccessfulCheck()) {
            return back()->with('error', $result->getFirstError());
        }

        return back()->with('success', __('admin/backend.learning-modes.updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?LearningMode $mode = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('learning_modes')->ignore($mode)],
            'tagline' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'prompt_directive' => 'required|string|min:20|max:2000',
            'icon' => 'required|string|max:60',
            'accent' => ['required', Rule::in(LearningMode::ACCENTS)],
            'position' => 'nullable|integer|min:0|max:999',
        ]);

        return $validated + [
            'position' => (int) $request->input('position', 0),
            'enabled' => $request->boolean('enabled'),
            'is_default' => $request->boolean('is_default'),
        ];
    }
}
