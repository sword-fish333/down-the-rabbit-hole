{{-- One page of the library.

     Rendered inside the list on first paint and returned on its own for search
     and infinite scroll, so this markup exists exactly once — the browser never
     re-implements a row. The trailing <li> carries the next cursor: an <li> is
     valid inside the <ul> it lands in, and parses standalone when appended. --}}
@php
    use App\Models\Conversation;

    $maxDepth = (int) config('platform.chat.max_depth');
    // The empty state belongs on a first page, never on the tail of a scroll:
    // a cursor means "append", and appending nothing is not an empty library.
    $firstPage = ! request()->filled('cursor');
@endphp

@forelse ($subjects as $subject)
    <li class="dth-subject-row group/row relative border-b border-border/50" data-subject-row>
        {{-- Selection lives outside the link, not inside it: a checkbox in an
             anchor is unreachable by keyboard in the way people expect. --}}
        <label class="dth-select absolute left-3 top-1/2 z-10 -translate-y-1/2 cursor-pointer p-1.5">
            <input type="checkbox" name="ids[]" value="{{ $subject->id }}" form="dth-subject-selection"
                   class="h-4 w-4 rounded border-border-strong bg-transparent"
                   aria-label="{{ $subject->displayTitle() }}">
        </label>

        <a href="{{ route('subject.show', $subject) }}"
           class="dth-row-link flex flex-col gap-1 px-3 py-3 transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-surface-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:flex-row sm:items-center sm:gap-3">

            <span class="dth-coord shrink-0">
                {{ str_pad($subject->current_depth, 2, '0', STR_PAD_LEFT) }}<span aria-hidden="true">/</span>{{ str_pad($maxDepth, 2, '0', STR_PAD_LEFT) }}
            </span>

            <span class="min-w-0 flex-1 truncate text-sm font-medium text-foreground">
                {{ $subject->displayTitle() }}
            </span>

            <span class="flex shrink-0 flex-wrap items-center gap-2 text-xs text-foreground-muted">
                @if ($source = $subject->sources->first())
                    <span class="inline-flex max-w-40 items-center gap-1 truncate rounded-full border border-border px-2 py-0.5">
                        <span class="material-symbols-outlined text-[0.85rem]" aria-hidden="true">link</span>
                        <span class="truncate">{{ $source->site }}</span>
                    </span>
                @endif

                @if ($subject->folder)
                    <span class="inline-flex max-w-32 items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[0.9rem]" aria-hidden="true">folder</span>
                        <span class="truncate">{{ $subject->folder->name }}</span>
                    </span>
                @endif

                @if ($subject->mastered_count)
                    <span class="inline-flex items-center gap-1 text-success">
                        <span class="material-symbols-outlined text-[0.9rem]" aria-hidden="true">verified</span>
                        {{ $subject->mastered_count }}
                        <span class="sr-only">{{ trans_choice('frontend.subjects.mastered', $subject->mastered_count, ['count' => $subject->mastered_count]) }}</span>
                    </span>
                @endif

                @if ($subject->isShared())
                    <span class="inline-flex items-center gap-1 rounded-full border border-border px-2 py-0.5">
                        <span class="material-symbols-outlined text-[0.85rem]" aria-hidden="true">public</span>
                        {{ __('frontend.subjects.status.shared') }}
                    </span>
                @endif

                {{-- "In progress" is the default state, so it says nothing here —
                     only the two states that ask something of the learner do. --}}
                @unless ($subject->status === Conversation::STATUS_EXPLORING)
                    <x-frontend.subject-status :subject="$subject" />
                @endunless

                <time datetime="{{ $subject->updated_at->toIso8601String() }}"
                      class="w-24 shrink-0 text-right text-foreground-muted/80">{{ $subject->updated_at->diffForHumans(short: true) }}</time>
            </span>
        </a>
    </li>
@empty
    @if ($firstPage)
        <li class="px-3 py-16 text-center">
            <span class="material-symbols-outlined text-[2rem] text-primary/70" aria-hidden="true">stairs</span>
            <p class="mt-3 font-display text-base font-semibold text-foreground">
                {{ request()->filled('q') || request()->filled('filter') ? __('frontend.subjects.no-matches') : __('frontend.subjects.empty-title') }}
            </p>
            <p class="mx-auto mt-1.5 max-w-md text-sm text-foreground-muted">
                {{ request()->filled('q') || request()->filled('filter') ? __('frontend.subjects.no-matches-body') : __('frontend.subjects.empty-body') }}
            </p>
        </li>
    @endif
@endforelse

<li hidden data-rows-meta
    data-next-cursor="{{ $subjects->hasMorePages() ? $subjects->nextCursor()->encode() : '' }}"
    data-count="{{ $subjects->count() }}"></li>
