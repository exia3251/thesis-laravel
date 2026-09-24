@extends('layouts.bare')

@section('title', 'Choose a new password - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)] px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="/shop">
                    <div class="text-3xl font-black tracking-tight sm:text-4xl">
                        <span class="text-[var(--primary)]">RANEY</span>
                        <span class="text-[var(--accent)]"> LUBRICANTS</span>
                    </div>
                    <div class="mt-1 text-[11px] uppercase tracking-[0.32em] text-[var(--muted)]">Trading</div>
                </a>
            </div>

            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm sm:p-8">
                <h1 class="text-2xl font-extrabold text-[var(--ink)]">Choose a new password</h1>
                <p class="mt-2 text-sm leading-6 text-[var(--muted)]">
                    At least {{ \App\Support\PasswordPolicy::MINIMUM }} characters. Setting it signs out anyone
                    already using this account, including you on other devices.
                </p>

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="/shop/reset-password" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-[var(--ink)]">Email</label>
                        <input id="email" name="email" type="email" required maxlength="150" autocomplete="username"
                               value="{{ old('email', $email) }}"
                               class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-[var(--ink)]">New password</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                               minlength="{{ \App\Support\PasswordPolicy::MINIMUM }}" maxlength="{{ \App\Support\PasswordPolicy::MAXIMUM }}"
                               class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-[var(--ink)]">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                               minlength="{{ \App\Support\PasswordPolicy::MINIMUM }}" maxlength="{{ \App\Support\PasswordPolicy::MAXIMUM }}"
                               class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                    </div>

                    <button type="submit"
                            class="flex w-full justify-center rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-110">
                        Save the new password
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-[var(--muted)]">
                    <a href="/shop/forgot-password" class="font-semibold text-[var(--primary)] hover:underline">Send me another link</a>
                </div>
            </section>
        </div>
    </div>
@endsection
