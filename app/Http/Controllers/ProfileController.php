<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $user->load('roles');

        return view('profile.show', [
            'user' => $user,
            'roles' => $user->roles,
        ]);
    }

    public function showPasswordResetForm(): View
    {
        return view('profile.reset-password');
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $emailChanged = $user->email !== $request->email;

        // Update name
        $user->name = $request->name;

        // Update email if changed
        if ($emailChanged) {
            $user->email = $request->email;
            $user->email_verified_at = null;
        }

        // Update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Send verification email if email changed
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $message = 'Profile updated successfully.';
        if ($emailChanged) {
            $message .= ' A verification email has been sent to your new email address. You will need to verify it before you can login again.';
        }

        return redirect()->route('profile.show')->with('success', $message);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Password updated successfully.');
    }
}
