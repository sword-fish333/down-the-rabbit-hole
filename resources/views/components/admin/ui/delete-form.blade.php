@props([
    'action',
    'confirm' => null,
    'label' => null,
    'iconOnly' => false,
])

{{-- Destructive actions are always a POSTed form with a native confirm, never a
     bare link — a GET that deletes is one prefetch away from a bad day. --}}
<form method="POST" action="{{ $action }}"
      onsubmit="return confirm('{{ $confirm ?? __('admin/frontend.general.confirm-delete') }}')"
      class="inline">
    @csrf
    @method('DELETE')

    @if ($iconOnly)
        <button type="submit"
                class="grid h-9 w-9 place-items-center rounded-lg text-muted-foreground transition hover:bg-danger/10 hover:text-danger focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger/40"
                aria-label="{{ $label ?? __('admin/frontend.general.delete') }}"
                title="{{ $label ?? __('admin/frontend.general.delete') }}">
            <x-admin.icon name="delete" class="text-lg" />
        </button>
    @else
        <x-admin.ui.button type="submit" variant="danger">
            <x-admin.icon name="delete" class="text-lg" />
            {{ $label ?? __('admin/frontend.general.delete') }}
        </x-admin.ui.button>
    @endif
</form>
