@extends('layouts.bare')

@section('title', 'Forgotten password - RANEY LUBRICANTS TRADING')

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
                <h1 class="text-2xl font-extrabold text-[var(--ink)]">Forgotten your password?</h1>
                <p class="mt-2 text-sm leading-6 text-[var(--muted)]">
                    Give us the email address on the account and we will send a link for setting a new one.
                    Nobody here can read your old password, so there is nothing to be told.
                </p>

                @if (session('sent'))
                    {{-- Deliberately the same whether or not we hold the
                         address, so the form cannot be used to find out who
                         shops here. --}}
                    <div class="mt-6 rounded-2xl border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-3.5 text-sm leading-6 text-[var(--primary)]">
                        {{ session('sent') }}
                    </div>

                    <p class="mt-4 text-xs leading-5 text-[var(--muted)]">
                        Nothing arrived? Check the spam folder, and make sure the address is the one you registered with.
                        You can ask again in a minute.
                    </p>
                @endif

                <form method="POST" action="/shop/forgot-password" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-[var(--ink)]">Email</label>
                        <input id="email" name="email" type="email" required maxlength="150" autocomplete="username"
                               value="{{ old('email') }}" placeholder="you@example.com"
                               class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="flex w-full justify-center rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-110">
                        Send the link
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-[var(--muted)]">
                    Remembered it? <a href="/shop/login" class="font-semibold text-[var(--primary)] hover:underline">Back to sign in</a>
                </div>
            </section>

            <p class="mt-6 text-center text-xs leading-5 text-[var(--muted)]">
                Staff accounts are not reset here. Ask an administrator, who can set one from the Users screen.
            </p>
        </div>
    </div>
@endsection
