<x-admin.layout :title="__('admin/frontend.learning-modes.new')">
    <x-admin.ui.page-header :title="__('admin/frontend.learning-modes.new')"
                            :subtitle="__('admin/frontend.learning-modes.new-subtitle')"
                            :back="route('admin.learning-mode.index')" />

    <form method="POST" action="{{ route('admin.learning-mode.store') }}">
        @csrf
        @include('admin.learning_modes.partials.form', ['mode' => $mode])
    </form>
</x-admin.layout>
