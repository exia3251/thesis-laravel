@extends('layouts.bare')

@section('title', $heading . ' - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)] px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="/shop" class="text-2xl font-black tracking-tight">
                    <span class="text-[var(--primary)]">RANEY</span><span class="text-[var(--accent)]"> LUBRICANTS</span>
                </a>
                <div class="mt-1 text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
            </div>

            <div class="rounded-[1.75rem] border border-[var(--line)] bg-[var(--card)] p-8 text-center shadow-xl backdrop-blur">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl {{ $ok ? 'bg-emerald-100' : 'bg-red-100' }}">
                    @if ($ok)
                        <svg class="h-8 w-8 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    @else
                        <svg class="h-8 w-8 text-red-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    @endif
                </div>

                <h1 class="mt-6 text-2xl font-black text-[var(--ink)]">{{ $heading }}</h1>
                <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ $message }}</p>

                <a href="/shop/login" class="mt-6 inline-block w-full rounded-xl bg-[var(--primary)] px-5 py-3 text-sm font-bold text-white transition hover:brightness-110">
                    Go to sign in
                </a>
                <a href="/shop" class="mt-3 inline-block text-sm font-semibold text-[var(--primary)] hover:underline">Browse the shop</a>
            </div>
        </div>
    </div>
@endsection
