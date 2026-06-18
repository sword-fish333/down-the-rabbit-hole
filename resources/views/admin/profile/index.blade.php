<x-admin.layout :title="__('admin/frontend.nav.profile')">
    @php($activeTab = session('active_profile_tab', 'profile'))
    @php($field = 'w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition placeholder:text-zinc-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-white')

    <div class="mx-auto max-w-3xl">

        {{-- Header card with avatar --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
                <form method="POST" action="{{ route('admin.profile.update-profile-img') }}"
                      enctype="multipart/form-data" class="relative shrink-0">
                    @csrf
                    @php($avatarUrl = $admin->profileImageUrl())
                    <img data-avatar-preview src="{{ $avatarUrl }}" alt="{{ $admin->name }}"
                         class="{{ $avatarUrl ? '' : 'hidden' }} h-24 w-24 rounded-full object-cover ring-4 ring-zinc-100 dark:ring-zinc-800">
                    <span class="{{ $avatarUrl ? 'hidden' : '' }} grid h-24 w-24 place-items-center rounded-full bg-brand-600 text-2xl font-semibold text-white ring-4 ring-zinc-100 dark:ring-zinc-800">
                        {{ $admin->initials() }}
                    </span>
                    <label class="absolute -right-1 -bottom-1 grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-brand-600 text-white shadow-md transition hover:bg-brand-700"
                           title="{{ __('admin/frontend.profile.change-photo') }}">
                        <i class="fa-solid fa-camera text-xs"></i>
                        <input data-avatar-input type="file" name="profile_img" accept="image/*" class="hidden">
                    </label>
                </form>

                <div class="text-center sm:text-left">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ $admin->name }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $admin->email }}</p>
                    <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700 dark:bg-brand-600/15 dark:text-brand-300">
                        <i class="fa-solid fa-shield-halved"></i>{{ __('admin/frontend.roles.'.$admin->role) }}
                    </span>
                </div>
            </div>
            @error('profile_img')
                <p class="mt-3 text-center text-xs text-rose-500 sm:text-left">{{ $message }}</p>
            @enderror
        </div>

        {{-- Tabs --}}
        <div class="mt-6 grid grid-cols-3 gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800/60">
            @foreach (['profile' => 'fa-user', 'password' => 'fa-lock', 'support' => 'fa-life-ring'] as $tab => $icon)
                <button data-tab="{{ $tab }}"
                    @class([
                        'flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-white text-brand-700 shadow-sm dark:bg-zinc-700 dark:text-white' => $activeTab === $tab,
                        'text-zinc-500 dark:text-zinc-400' => $activeTab !== $tab,
                    ])>
                    <i class="fa-solid {{ $icon }}"></i>
                    <span class="hidden sm:inline">{{ __('admin/frontend.profile.tab-'.$tab) }}</span>
                </button>
            @endforeach
        </div>

        {{-- Panel: Profile --}}
        <div data-tab-panel="profile" class="{{ $activeTab === 'profile' ? '' : 'hidden' }} mt-5">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('admin/frontend.profile.personal-info') }}</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin/frontend.profile.personal-info-hint') }}</p>

                <form method="POST" action="{{ route('admin.profile.update-profile') }}" class="mt-6 space-y-5">
                    @csrf
                    <x-admin.ui.input name="name" :label="__('admin/frontend.profile.display-name')" :value="$admin->name" :required="true" />
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-admin.ui.input name="first_name" :label="__('admin/frontend.profile.first-name')" :value="$admin->first_name" />
                        <x-admin.ui.input name="last_name" :label="__('admin/frontend.profile.last-name')" :value="$admin->last_name" />
                    </div>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-admin.ui.input name="email" type="email" :label="__('admin/frontend.auth.email')" :value="$admin->email" :required="true" icon="fa-envelope" />
                        <x-admin.ui.input name="phone" :label="__('admin/frontend.profile.phone')" :value="$admin->phone" icon="fa-phone" />
                    </div>
                    <div class="flex justify-end pt-1">
                        <x-admin.ui.button type="submit">
                            <i class="fa-solid fa-floppy-disk"></i>{{ __('admin/frontend.profile.save-changes') }}
                        </x-admin.ui.button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel: Password --}}
        <div data-tab-panel="password" class="{{ $activeTab === 'password' ? '' : 'hidden' }} mt-5">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('admin/frontend.profile.change-password') }}</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin/frontend.profile.change-password-hint') }}</p>

                <form method="POST" action="{{ route('admin.profile.update-password') }}" class="mt-6 space-y-5">
                    @csrf
                    <x-admin.ui.input name="current_password" type="password" :label="__('admin/frontend.profile.current-password')" :required="true" icon="fa-lock" autocomplete="current-password">
                        <button type="button" data-password-toggle="#current_password" class="absolute top-1/2 right-3.5 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </x-admin.ui.input>
                    <x-admin.ui.input name="password" type="password" :label="__('admin/frontend.profile.new-password')" :required="true" icon="fa-key" autocomplete="new-password">
                        <button type="button" data-password-toggle="#password" class="absolute top-1/2 right-3.5 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </x-admin.ui.input>
                    <x-admin.ui.input name="password_confirmation" type="password" :label="__('admin/frontend.profile.confirm-password')" :required="true" icon="fa-key" autocomplete="new-password" />
                    <div class="flex justify-end pt-1">
                        <x-admin.ui.button type="submit">
                            <i class="fa-solid fa-shield-halved"></i>{{ __('admin/frontend.profile.update-password') }}
                        </x-admin.ui.button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel: Support --}}
        <div data-tab-panel="support" class="{{ $activeTab === 'support' ? '' : 'hidden' }} mt-5">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('admin/frontend.profile.request-support') }}</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin/frontend.profile.request-support-hint') }}</p>

                <form method="POST" action="{{ route('admin.profile.request-support') }}" class="mt-6 space-y-5">
                    @csrf
                    <x-admin.ui.input name="title" :label="__('admin/frontend.profile.support-title')" :required="true" :placeholder="__('admin/frontend.profile.support-title-placeholder')" />

                    <div>
                        <label for="topic" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('admin/frontend.profile.support-topic') }}<span class="text-rose-500"> *</span>
                        </label>
                        <select id="topic" name="topic" class="{{ $field }}">
                            @foreach ($supportTopics as $topic)
                                <option value="{{ $topic }}" @selected(old('topic') === $topic)>
                                    {{ __('admin/frontend.profile.topics.'.$topic) }}
                                </option>
                            @endforeach
                        </select>
                        @error('topic')<p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="message" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('admin/frontend.profile.support-message') }}<span class="text-rose-500"> *</span>
                        </label>
                        <textarea id="message" name="message" rows="5" class="{{ $field }}"
                                  placeholder="{{ __('admin/frontend.profile.support-message-placeholder') }}">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end pt-1">
                        <x-admin.ui.button type="submit">
                            <i class="fa-solid fa-paper-plane"></i>{{ __('admin/frontend.profile.send-request') }}
                        </x-admin.ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin.layout>
