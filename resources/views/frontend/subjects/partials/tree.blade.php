{{-- The editable tree. Recurses through itself; `$folders` and `$subjects` are
     one level, `$depth` only drives indentation.

     Every mutation here is a real form post to a real route. Drag-and-drop is
     layered on top of the same endpoints by subjects.js — so a learner without
     a pointer, or without JavaScript, can still file everything. --}}
@php($depth = $depth ?? 0)

<ul class="space-y-px" @if ($depth === 0) data-tree-level="0" @endif>
    @foreach ($folders as $folder)
        <li data-tree-node data-node-type="folder" data-node-id="{{ $folder->id }}"
            data-name="{{ Str::lower($folder->name) }}">
            <details class="group/folder" data-folder-details data-folder-id="{{ $folder->id }}">
                <summary class="dth-tree-row group/row flex cursor-pointer list-none items-center gap-2 rounded-lg py-2 pr-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                         style="padding-left: {{ 0.5 + $depth * 0.85 }}rem"
                         draggable="true"
                         data-drag-type="folder" data-drag-id="{{ $folder->id }}"
                         data-drop-folder="{{ $folder->id }}">
                    <span class="material-symbols-outlined shrink-0 text-[1.05rem] text-foreground-muted transition-transform duration-(--motion-state) ease-(--ease-snap) group-open/folder:rotate-90"
                          aria-hidden="true">chevron_right</span>
                    <span class="material-symbols-outlined shrink-0 text-[1.05rem] text-accent" aria-hidden="true">folder</span>

                    <span class="min-w-0 flex-1 truncate font-medium text-foreground">{{ $folder->name }}</span>

                    @if ($folder->conversations->isNotEmpty())
                        <span class="dth-coord shrink-0">{{ $folder->conversations->count() }}</span>
                    @endif

                    {{-- Row actions. Revealed on hover, and permanently present
                         for keyboard and touch — a hidden-until-hover control
                         that can't be reached any other way is not a control. --}}
                    <span class="dth-row-actions flex shrink-0 items-center gap-0.5">
                        <button type="button" data-new-subfolder="{{ $folder->id }}" data-folder-name="{{ $folder->name }}"
                                title="{{ __('frontend.subjects.new-subfolder') }}"
                                class="grid h-7 w-7 place-items-center rounded-md text-foreground-muted transition hover:bg-surface-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">create_new_folder</span>
                            <span class="sr-only">{{ __('frontend.subjects.new-subfolder') }}</span>
                        </button>

                        <button type="button" data-rename-folder="{{ $folder->id }}"
                                title="{{ __('frontend.subjects.rename') }}"
                                class="grid h-7 w-7 place-items-center rounded-md text-foreground-muted transition hover:bg-surface-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">edit</span>
                            <span class="sr-only">{{ __('frontend.subjects.rename') }}</span>
                        </button>

                        <form method="POST" action="{{ route('subjects.folders.destroy', $folder) }}" class="contents">
                            @csrf
                            @method('DELETE')
                            <button type="submit" data-confirm-delete
                                    title="{{ __('frontend.subjects.delete-folder') }}"
                                    class="grid h-7 w-7 place-items-center rounded-md text-foreground-muted transition hover:bg-danger/10 hover:text-danger focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">delete</span>
                                <span class="sr-only">{{ __('frontend.subjects.delete-folder') }}</span>
                            </button>
                        </form>
                    </span>
                </summary>

                {{-- Rename, in place. Hidden until asked for; a plain form either way. --}}
                <form method="POST" action="{{ route('subjects.folders.update', $folder) }}"
                      data-rename-form="{{ $folder->id }}" hidden
                      class="flex items-center gap-2 py-1.5 pr-2"
                      style="padding-left: {{ 1.35 + $depth * 0.85 }}rem">
                    @csrf
                    @method('PATCH')
                    <label class="sr-only" for="dth-rename-{{ $folder->id }}">{{ __('frontend.subjects.folder-name') }}</label>
                    <input id="dth-rename-{{ $folder->id }}" type="text" name="name" value="{{ $folder->name }}"
                           maxlength="80" required
                           class="min-w-0 flex-1 rounded-lg border border-border-strong bg-surface/60 px-2.5 py-1.5 text-sm text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <button type="submit"
                            class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90">
                        {{ __('frontend.subjects.save') }}
                    </button>
                </form>

                <div data-children>
                    @include('frontend.subjects.partials.tree', [
                        'folders' => $folder->children,
                        'subjects' => $folder->conversations,
                        'depth' => $depth + 1,
                    ])
                </div>
            </details>
        </li>
    @endforeach

    @foreach ($subjects as $subject)
        <li data-tree-node data-node-type="subject" data-node-id="{{ $subject->id }}"
            data-name="{{ Str::lower($subject->displayTitle()) }}">
            <a href="{{ route('subject.show', $subject) }}"
               draggable="true" data-drag-type="subject" data-drag-id="{{ $subject->id }}"
               class="dth-tree-row group/row flex items-center gap-2 rounded-lg py-2 pr-2 text-sm text-foreground-muted transition duration-(--motion-feedback) hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
               style="padding-left: {{ 1.85 + $depth * 0.85 }}rem">
                <span class="dth-coord w-5 shrink-0 text-right">{{ str_pad($subject->current_depth, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="min-w-0 flex-1 truncate">{{ $subject->displayTitle() }}</span>
                <x-frontend.subject-status :subject="$subject" compact />
                <span class="material-symbols-outlined shrink-0 text-[1rem] opacity-0 transition-opacity duration-(--motion-feedback) group-hover/row:opacity-60"
                      aria-hidden="true">drag_indicator</span>
            </a>
        </li>
    @endforeach
</ul>
