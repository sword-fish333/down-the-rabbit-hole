@props(['subject', 'active' => false])

{{-- One line in the rail. The depth number leads, because "where was I" is the
     question this list answers — the title alone would make it a chat history. --}}
<a href="{{ route('subject.show', $subject) }}"
   @if ($active) aria-current="page" @endif
   @class([
       'group/row flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
       'bg-primary/12 text-foreground' => $active,
       'text-foreground-muted hover:bg-surface-muted hover:text-foreground' => ! $active,
   ])>
    <span class="dth-coord w-5 shrink-0 text-right">{{ str_pad($subject->current_depth, 2, '0', STR_PAD_LEFT) }}</span>
    <span class="min-w-0 flex-1 truncate">{{ $subject->displayTitle() }}</span>
    <x-frontend.subject-status :subject="$subject" compact />
</a>
