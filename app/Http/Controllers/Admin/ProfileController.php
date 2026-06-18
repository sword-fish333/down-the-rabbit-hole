<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Admin\RequestSupportMail;
use App\Models\Admin;
use App\Services\ValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('admin.profile.index', [
            'admin' => auth('admin')->user(),
            'supportTopics' => config('platform.support_topics'),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        session()->flash('active_profile_tab', 'profile');

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:60',
            'first_name' => 'nullable|string|max:60',
            'last_name' => 'nullable|string|max:60',
            'phone' => 'nullable|string|max:20',
            'email' => ['required', 'email', Rule::unique('admins')->ignore(auth('admin')->id())],
        ]);

        auth('admin')->user()->update($validated);

        return back()->with('success', __('admin/backend.profile.profile-updated-successfully'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        session()->flash('active_profile_tab', 'password');

        $validated = $request->validate([
            'current_password' => 'required|string|max:60',
            'password' => 'required|string|confirmed|min:8|max:60',
        ]);

        $admin = auth('admin')->user();

        $check = (new ValidationService);
        if (!Hash::check($validated['current_password'], $admin->password)) {
            $check->errorEncountered(__('admin/backend.auth.current-password-incorrect'));
        }

        if (!$check->isSuccessfulCheck()) {
            return back()->with('error', $check->getFirstError());
        }

        $admin->update(['password' => $validated['password']]);

        return back()->with('success', __('admin/backend.profile.password-updated-successfully'));
    }

    public function requestSupport(Request $request): RedirectResponse
    {
        session()->flash('active_profile_tab', 'support');

        $validated = $request->validate([
            'title' => 'required|string|min:2|max:200',
            'topic' => ['required', 'string', Rule::in(config('platform.support_topics'))],
            'message' => 'required|string|min:2|max:5000',
        ]);

        Mail::to(config('platform.support_email'))
            ->send(new RequestSupportMail(auth('admin')->user(), $validated));

        return back()->with('success', __('admin/backend.profile.support-request-made-successfully'));
    }

    public function updateProfileImg(Request $request): RedirectResponse
    {
        session()->flash('active_profile_tab', 'profile');

        $request->validate([
            'profile_img' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $admin = auth('admin')->user();

        try {
            deleteFile($admin->profile_image, Admin::IMAGES_DIRECTORY);
            $admin->update([
                'profile_image' => saveFileToStorage($request->file('profile_img'), Admin::IMAGES_DIRECTORY),
            ]);

            return back()->with('success', __('admin/backend.profile.profile-image-updated-successfully'));
        } catch (\Throwable $e) {
            fullLog($e);

            return back()->with('error', __('admin/backend.profile.an-error-occurred'));
        }
    }
}
