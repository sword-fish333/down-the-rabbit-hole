@php($viewerId = auth()->id())

{{-- The board.

     A real <table>, because it is one: a header row that names the columns, a
     caption that names the board, and cells a screen reader can associate. The
     styling is ours; the semantics are the platform's.

     The whole row is the link — an anchor stretched over the cell so the hit
     target is the row on a phone without nesting interactive elements. Wide
     content scrolls inside its own container; the page body never does. --}}
<div class="overflow-x-auto rounded-2xl border border-border/70 bg-surface/40 backdrop-blur-sm">
    <table class="w-full border-collapse text-left">
        <caption class="sr-only">
            {{ __('frontend.rankings.board.'.$board) }} — {{ __('frontend.rankings.period.'.$period) }}
        </caption>

        <thead>
            <tr class="border-b border-border/70">
                <th scope="col" class="dth-coord px-4 py-3 font-normal sm:px-5">{{ __('frontend.rankings.rank') }}</th>
                <th scope="col" class="dth-coord px-2 py-3 font-normal">{{ __('frontend.rankings.learner') }}</th>
                <th scope="col" class="dth-coord px-4 py-3 text-right font-normal sm:px-5">{{ __('frontend.rankings.result') }}</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($rows as $row)
                @php($learner = $row['user'])
                @php($isViewer = $learner->id === $viewerId)

                <tr @class([
                        'dth-board-row border-b border-border/40 last:border-0',
                        'is-you' => $isViewer,
                    ])
                    @if ($row['rank'] <= 3) data-podium="{{ $row['rank'] }}" @endif>

                    <th scope="row" class="px-4 py-3 font-normal sm:px-5">
                        <x-frontend.rank-badge :rank="$row['rank']" />
                    </th>

                    <td class="px-2 py-3">
                        <a href="{{ route('learners.show', $learner) }}"
                           class="dth-row-link flex min-w-0 items-center gap-2.5 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <x-frontend.avatar :user="$learner" size="h-8 w-8" text="text-xs" class="shrink-0" />

                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-foreground">{{ $learner->fullName() }}</span>
                                @if ($isViewer)
                                    <span class="dth-coord block text-primary">{{ __('frontend.rankings.you') }}</span>
                                @endif
                            </span>

                            <span class="sr-only">{{ __('frontend.rankings.view-profile', ['name' => $learner->fullName()]) }}</span>
                        </a>
                    </td>

                    <td class="px-4 py-3 text-right sm:px-5">
                        <span class="font-display text-base font-semibold tabular-nums text-foreground">
                            {{ trans_choice('frontend.rankings.board.'.$board.'-unit', $row['value'], ['count' => number_format($row['value'])]) }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
