@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6 text-center">Verify Your Email</h2>

    <p class="text-gray-700 mb-4">
        Thanks for signing up! Before getting started, please verify your email address using the link we just sent you.
        If you did not receive the email, you can request another after a short wait.
    </p>

    @if (session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            A new verification link has been sent to the email address on your account.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mb-4" id="verification-resend-form">
        @csrf
        <button
            type="submit"
            id="verification-resend-button"
            @if ($resendAvailableIn > 0) disabled aria-disabled="true" @endif
            class="bg-blue-500 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-2 px-4 rounded"
        >
            Resend Verification Email
        </button>
        <p id="verification-resend-countdown" class="mt-2 text-sm text-gray-600 @if($resendAvailableIn === 0) hidden @endif">
            You can resend again in <span id="verification-resend-seconds">{{ $resendAvailableIn }}</span> seconds.
        </p>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('verification-resend-button');
    const countdown = document.getElementById('verification-resend-countdown');
    const secondsEl = document.getElementById('verification-resend-seconds');
    let remaining = {{ (int) $resendAvailableIn }};

    if (remaining <= 0) {
        return;
    }

    button.disabled = true;

    const tick = function () {
        secondsEl.textContent = String(remaining);

        if (remaining <= 0) {
            button.disabled = false;
            countdown.classList.add('hidden');
            return;
        }

        remaining -= 1;
        window.setTimeout(tick, 1000);
    };

    tick();
});
</script>
@endsection
