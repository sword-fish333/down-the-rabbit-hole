<x-frontend.layout :title="__('frontend.subjects.organization-title')" shell>
    {{-- ===================================================================
         Organisation — the learner's own shelves.

         A vault, not a dashboard: one tree, four verbs (new, rename, move,
         delete), and a filter. Deleting a folder never deletes a descent, and
         the copy says so where the button is, not in a help page.
         =================================================================== --}}
    {{-- The two endpoints a drop can hit, with the id left as a placeholder so
         the URLs still come from the router rather than being built in JS. --}}
    <section class="mx-auto w-full max-w-3xl px-4 py-8 sm:px-6 lg:px-8"
             data-subject-tree
             data-folder-endpoint="{{ route('subjects.folders.update', ['folder' => '__ID__']) }}"
             data-subject-endpoint="{{ route('subject.file', ['conversation' => '__ID__']) }}"
             data-confirm-label="{{ __('frontend.subjects.confirm-delete-folder') }}">

        <div class="flex flex-wrap items-start gap-3">
            <div class="min-w-0">
                <h1 class="font-display text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('frontend.subjects.organization-title') }}
                </h1>
                <p class="mt-1.5 text-sm text-foreground-muted">{{ __('frontend.subjects.organization-subtitle') }}</p>
            </div>

            <a href="{{ route('subjects.index') }}"
               class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-border px-3 py-2 text-sm text-foreground-muted transition duration-(--motion-feedback) hover:border-primary/40 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">history</span>
                {{ __('frontend.sidebar.all') }}
            </a>
        </div>

        {{-- Toolbar: filter the tree, and add a shelf. One create form serves the
             whole page — the "new subfolder" buttons just aim it at a parent. --}}
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start">
            <label class="dth-composer flex min-w-0 flex-1 items-center gap-2 rounded-xl border border-border-strong bg-surface/60 px-3 py-2">
                <span class="material-symbols-outlined text-[1.15rem] text-foreground-muted" aria-hidden="true">search</span>
                <span class="sr-only">{{ __('frontend.subjects.tree-search-label') }}</span>
                <input type="search" data-tree-search autocomplete="off" maxlength="120"
                       placeholder="{{ __('frontend.subjects.tree-search-placeholder') }}"
                       class="min-w-0 flex-1 border-0 bg-transparent text-sm text-foreground placeholder:text-foreground-muted/70 focus:outline-none">
            </label>

            <form method="POST" action="{{ route('subjects.folders.store') }}"
                  class="flex min-w-0 shrink-0 flex-col gap-1.5">
                @csrf
                <div class="flex items-center gap-2">
                    <label class="sr-only" for="dth-folder-name">{{ __('frontend.subjects.folder-name') }}</label>
                    <input id="dth-folder-name" type="text" name="name" required maxlength="80"
                           data-new-folder-name
                           placeholder="{{ __('frontend.subjects.folder-name') }}"
                           class="w-44 rounded-xl border border-border-strong bg-surface/60 px-3 py-2 text-sm text-foreground placeholder:text-foreground-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <input type="hidden" name="parent_id" value="" data-new-folder-parent>
                    <button type="submit"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <span class="material-symbols-outlined text-[1.05rem]" aria-hidden="true">create_new_folder</span>
                        {{ __('frontend.subjects.add-folder') }}
                    </button>
                </div>

                {{-- Where the new folder will land. Only shown once it isn't root. --}}
                <p data-new-folder-hint hidden class="dth-coord flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[0.9rem]" aria-hidden="true">subdirectory_arrow_right</span>
                    <span data-new-folder-hint-text></span>
                    <button type="button" data-new-folder-reset
                            class="underline decoration-dotted transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        {{ __('frontend.subjects.folder-root') }}
                    </button>
                </p>
            </form>
        </div>

        @error('name')
            <p class="mt-2 text-xs text-danger" role="alert">{{ $message }}</p>
        @enderror

        {{-- The tree. The whole panel is the root drop zone, so dragging a
             subject out of a folder has an obvious target: the empty space. --}}
        <div class="mt-6 rounded-2xl border border-border/70 bg-surface/30 p-2 backdrop-blur-sm"
             data-drop-root aria-label="{{ __('frontend.subjects.tree-label') }}">
            @if ($folders->isEmpty() && $unfiled->isEmpty())
                <div class="px-4 py-14 text-center">
                    <span class="material-symbols-outlined text-[2rem] text-accent/70" aria-hidden="true">folder_open</span>
                    <p class="mt-3 font-display text-base font-semibold text-foreground">{{ __('frontend.subjects.tree-empty-title') }}</p>
                    <p class="mx-auto mt-1.5 max-w-sm text-sm text-foreground-muted">{{ __('frontend.subjects.tree-empty-body') }}</p>
                </div>
            @else
                @include('frontend.subjects.partials.tree', [
                    'folders' => $folders,
                    'subjects' => collect(),
                    'depth' => 0,
                ])

                @if ($unfiled->isNotEmpty())
                    <p class="dth-coord mt-4 border-t border-border/50 px-2 pt-3">{{ __('frontend.subjects.unfiled') }}</p>
                    @include('frontend.subjects.partials.tree', [
                        'folders' => collect(),
                        'subjects' => $unfiled,
                        'depth' => 0,
                    ])
                @endif
            @endif

            <p data-tree-no-matches hidden class="px-4 py-10 text-center text-sm text-foreground-muted">
                {{ __('frontend.subjects.no-matches') }}
            </p>
        </div>

        <p class="mt-3 flex items-start gap-1.5 text-xs leading-relaxed text-foreground-muted/80">
            <span class="material-symbols-outlined mt-px text-[1rem]" aria-hidden="true">info</span>
            {{ __('frontend.subjects.tree-note') }}
        </p>

        {{-- One form, reused by every drop: JS fills the action and the payload. --}}
        <form id="dth-move-form" method="POST" hidden>
            @csrf
            @method('PATCH')
        </form>
    </section>

    @push('scripts')
        <script src="{{ loadFiles('js/frontend/subjects.js') }}" defer></script>
    @endpush
</x-frontend.layout>
