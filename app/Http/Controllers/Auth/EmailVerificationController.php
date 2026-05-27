<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailVerificationToken;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public const RESEND_COOLDOWN_SECONDS = 60;

    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            if (! $user->hasAnyRole()) {
                return redirect()
                    ->route('profile.show')
                    ->with('info', 'Your email address is verified. Please contact an administrator to be assigned roles before you can use the application.');
            }

            return redirect()->route('dashboard');
        }

        return view('auth.verify-email', [
            'resendAvailableIn' => $this->secondsUntilResendAvailable($request),
        ]);
    }

    public function verify(Request $request, int $id, string $token): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if (! EmailVerificationToken::validate($user, $token)) {
            abort(403, 'This verification link is invalid or has expired. Please sign in and request a new verification email.');
        }

        EmailVerificationToken::forget($user);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        User::ensureRoleManagerExists();

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectAfterVerified($user->fresh());
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterVerified($user);
        }

        $seconds = $this->secondsUntilResendAvailable($request);

        if ($seconds > 0) {
            return back()->with(
                'error',
                "Please wait {$seconds} more seconds before requesting another verification email.",
            );
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'We could not send the verification email. Please check mail settings or contact an administrator.',
            );
        }

        self::markVerificationEmailSent($request);

        return back()->with('status', 'verification-link-sent');
    }

    public static function markVerificationEmailSent(Request $request): void
    {
        $request->session()->put('verification_link_sent_at', now()->timestamp);
    }

    private function secondsUntilResendAvailable(Request $request): int
    {
        $sentAt = $request->session()->get('verification_link_sent_at');

        if ($sentAt === null) {
            return 0;
        }

        return max(0, self::RESEND_COOLDOWN_SECONDS - (now()->timestamp - (int) $sentAt));
    }

    private function redirectAfterVerified(User $user): RedirectResponse
    {
        if (! $user->hasAnyRole()) {
            return redirect()
                ->route('profile.show')
                ->with('info', 'Your email address is verified. Please contact an administrator to be assigned roles before you can use the application.');
        }

        return redirect()->route('dashboard');
    }
}
