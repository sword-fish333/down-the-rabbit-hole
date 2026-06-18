<x-admin.layout :title="__('admin/frontend.nav.profile')">
    @php($activeTab = session('active_profile_tab', 'profile'))
    @php($field = 'w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-sm text-foreground shadow-sm transition placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/25 focus:outline-none')

    <div class="mx-auto max-w-3xl">

        {{-- Header card with avatar --}}
        <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
                <form method="POST" action="{{ route('admin.profile.update-profile-img') }}"
                      enctype="multipart/form-data" class="relative shrink-0">
                    @csrf
                    @php($avatarUrl = $admin->profileImageUrl())
                    <img data-avatar-preview src="{{ $avatarUrl }}" alt="{{ $admin->name }}"
                         class="{{ $avatarUrl ? '' : 'hidden' }} h-24 w-24 rounded-full object-cover ring-4 ring-muted">
                    <span class="{{ $avatarUrl ? 'hidden' : '' }} grid h-24 w-24 place-items-center rounded-full bg-primary text-2xl font-semibold text-primary-foreground ring-4 ring-muted">
                        {{ $admin->initials() }}
                    </span>
                    <label class="absolute -right-1 -bottom-1 grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-primary text-primary-foreground shadow-md transition hover:bg-primary/90"
                           title="{{ __('admin/frontend.profile.change-photo') }}">
                        <x-admin.icon name="photo_camera" class="text-base" />
                        <input data-avatar-input type="file" name="profile_img" accept="image/*" class="hidden">
                    </label>
                </form>

                <div class="text-center sm:text-left">
                    <h2 class="text-lg font-bold text-foreground">{{ $admin->name }}</h2>
                    <p class="text-sm text-muted-foreground">{{ $admin->email }}</p>
                    <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        <x-admin.icon name="verified_user" filled class="text-sm" />{{ __('admin/frontend.roles.'.$admin->role) }}
                    </span>
                </div>
            </div>
            @error('profile_img')
                <p class="mt-3 text-center text-xs text-danger sm:text-left">{{ $message }}</p>
            @enderror
        </div>

        {{-- Tabs --}}
        <div role="tablist" aria-label="{{ __('admin/frontend.nav.profile') }}"
             class="mt-6 grid grid-cols-3 gap-1 rounded-xl bg-muted p-1">
            @foreach (['profile' => 'person', 'password' => 'lock', 'support' => 'support_agent'] as $tab => $icon)
                <button data-tab="{{ $tab }}" type="button"
                    role="tab" id="{{ $tab }}-tab" aria-controls="{{ $tab }}-panel"
                    aria-selected="{{ $activeTab === $tab ? 'true' : 'false' }}"
                    @class([
                        'flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-surface text-primary shadow-sm' => $activeTab === $tab,
                        'text-muted-foreground hover:text-foreground' => $activeTab !== $tab,
                    ])>
                    <x-admin.icon :name="$icon" class="text-lg" />
                    <span class="hidden sm:inline">{{ __('admin/frontend.profile.tab-'.$tab) }}</span>
                </button>
            @endforeach
        </div>

        {{-- Panel: Profile --}}
        <div data-tab-panel="profile" role="tabpanel" id="profile-panel" aria-labelledby="profile-tab"
             class="{{ $activeTab === 'profile' ? '' : 'hidden' }} mt-5">
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                <h3 class="text-base font-semibold text-foreground">{{ __('admin/frontend.profile.personal-info') }}</h3>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('admin/frontend.profile.personal-info-hint') }}</p>

                <form method="POST" action="{{ route('admin.profile.update-profile') }}" class="mt-6 space-y-5">
                    @csrf
                    <x-admin.ui.input name="name" :label="__('admin/frontend.profile.display-name')" :value="$admin->name" :required="true" />
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-admin.ui.input name="first_name" :label="__('admin/frontend.profile.first-name')" :value="$admin->first_name" />
                        <x-admin.ui.input name="last_name" :label="__('admin/frontend.profile.last-name')" :value="$admin->last_name" />
                    </div>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-admin.ui.input name="email" type="email" :label="__('admin/frontend.auth.email')" :value="$admin->email" :required="true" icon="mail" />
                        <x-admin.ui.input name="phone" :label="__('admin/frontend.profile.phone')" :value="$admin->phone" icon="call" />
                    </div>
                    <div class="flex justify-end pt-1">
                        <x-admin.ui.button type="submit">
                            <x-admin.icon name="save" class="text-lg" />{{ __('admin/frontend.profile.save-changes') }}
                        </x-admin.ui.button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel: Password --}}
        <div data-tab-panel="password" role="tabpanel" id="password-panel" aria-labelledby="password-tab"
             class="{{ $activeTab === 'password' ? '' : 'hidden' }} mt-5">
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                <h3 class="text-base font-semibold text-foreground">{{ __('admin/frontend.profile.change-password') }}</h3>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('admin/frontend.profile.change-password-hint') }}</p>

                <form method="POST" action="{{ route('admin.profile.update-password') }}" class="mt-6 space-y-5">
                    @csrf
                    <x-admin.ui.input name="current_password" type="password" :label="__('admin/frontend.profile.current-password')" :required="true" icon="lock" autocomplete="current-password">
                        <button type="button" data-password-toggle="#current_password" class="absolute top-1/2 right-3.5 -translate-y-1/2 text-muted-foreground transition hover:text-foreground">
                            <x-admin.icon name="visibility" class="text-xl" />
                        </button>
                    </x-admin.ui.input>
                    <x-admin.ui.input name="password" type="password" :label="__('admin/frontend.profile.new-password')" :required="true" icon="key" autocomplete="new-password">
                        <button type="button" data-password-toggle="#password" class="absolute top-1/2 right-3.5 -translate-y-1/2 text-muted-foreground transition hover:text-foreground">
                            <x-admin.icon name="visibility" class="text-xl" />
                        </button>
                    </x-admin.ui.input>
                    <x-admin.ui.input name="password_confirmation" type="password" :label="__('admin/frontend.profile.confirm-password')" :required="true" icon="key" autocomplete="new-password" />
                    <div class="flex justify-end pt-1">
                        <x-admin.ui.button type="submit">
                            <x-admin.icon name="lock_reset" class="text-lg" />{{ __('admin/frontend.profile.update-password') }}
                        </x-admin.ui.button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel: Support --}}
        <div data-tab-panel="support" role="tabpanel" id="support-panel" aria-labelledby="support-tab"
             class="{{ $activeTab === 'support' ? '' : 'hidden' }} mt-5">
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                <h3 class="text-base font-semibold text-foreground">{{ __('admin/frontend.profile.request-support') }}</h3>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('admin/frontend.profile.request-support-hint') }}</p>

                <form method="POST" action="{{ route('admin.profile.request-support') }}" class="mt-6 space-y-5">
                    @csrf
                    <x-admin.ui.input name="title" :label="__('admin/frontend.profile.support-title')" :required="true" :placeholder="__('admin/frontend.profile.support-title-placeholder')" />

                    <div>
                        <label for="topic" class="mb-1.5 block text-sm font-medium text-foreground">
                            {{ __('admin/frontend.profile.support-topic') }}<span class="text-danger"> *</span>
                        </label>
                        <select id="topic" name="topic" class="{{ $field }}">
                            @foreach ($supportTopics as $topic)
                                <option value="{{ $topic }}" @selected(old('topic') === $topic)>
                                    {{ __('admin/frontend.profile.topics.'.$topic) }}
                                </option>
                            @endforeach
                        </select>
                        @error('topic')<p class="mt-1.5 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="message" class="mb-1.5 block text-sm font-medium text-foreground">
                            {{ __('admin/frontend.profile.support-message') }}<span class="text-danger"> *</span>
                        </label>
                        <textarea id="message" name="message" rows="5" class="{{ $field }}"
                                  placeholder="{{ __('admin/frontend.profile.support-message-placeholder') }}">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1.5 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end pt-1">
                        <x-admin.ui.button type="submit">
                            <x-admin.icon name="send" class="text-lg" />{{ __('admin/frontend.profile.send-request') }}
                        </x-admin.ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin.layout>
