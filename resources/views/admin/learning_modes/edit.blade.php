<x-admin.layout :title="$mode->name">
    <x-admin.ui.page-header :title="$mode->name"
                            :subtitle="__('admin/frontend.learning-modes.edit-subtitle')"
                            :back="route('admin.learning-mode.index')">
        <x-slot:actions>
            <x-admin.ui.delete-form :action="route('admin.learning-mode.destroy', $mode)"
                                    :confirm="__('admin/frontend.learning-modes.confirm-delete', ['name' => $mode->name])" />
        </x-slot:actions>
    </x-admin.ui.page-header>

    <form method="POST" action="{{ route('admin.learning-mode.update', $mode) }}">
        @csrf
        @method('PUT')
        @include('admin.learning_modes.partials.form', ['mode' => $mode])
    </form>
</x-admin.layout>
