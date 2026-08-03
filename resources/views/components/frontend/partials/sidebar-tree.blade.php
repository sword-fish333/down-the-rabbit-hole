{{-- The rail's folder view: a read-only tree built on native <details>, so
     expanding a folder costs no JavaScript and works before app.js loads.
     Editing the tree is a different job and lives on the organisation page.

     Recurses through itself; `$folders` and `$subjects` are one level. --}}
<ul class="space-y-0.5">
    @foreach ($folders as $folder)
        <li>
            <details class="group/folder">
                <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm text-foreground-muted transition duration-(--motion-feedback) hover:bg-surface-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <span class="material-symbols-outlined shrink-0 text-[1rem] transition-transform duration-(--motion-state) ease-(--ease-snap) group-open/folder:rotate-90"
                          aria-hidden="true">chevron_right</span>
                    <span class="min-w-0 flex-1 truncate">{{ $folder->name }}</span>
                    @if ($folder->conversations->isNotEmpty())
                        <span class="dth-coord shrink-0">{{ $folder->conversations->count() }}</span>
                    @endif
                </summary>

                <div class="ml-3 border-l border-border/50 pl-1.5">
                    @include('components.frontend.partials.sidebar-tree', [
                        'folders' => $folder->children,
                        'subjects' => $folder->conversations,
                        'activeId' => $activeId,
                    ])
                </div>
            </details>
        </li>
    @endforeach

    @foreach ($subjects as $subject)
        <li><x-frontend.subject-link :subject="$subject" :active="$subject->id === $activeId" /></li>
    @endforeach
</ul>
