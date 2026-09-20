{{-- One avatar, used wherever a staff member is named.
     Falls back to initials on a colour derived from the account id, so a
     person looks the same everywhere even without a photo uploaded.

     @param \App\Models\User $user
     @param string $size  tailwind sizing, e.g. 'h-9 w-9'
     @param string $text  tailwind text size for the initials --}}
@php
    $size = $size ?? 'h-9 w-9';
    $text = $text ?? 'text-xs';
@endphp

@if ($user?->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->full_name }}"
         class="{{ $size }} shrink-0 rounded-full object-cover ring-2 ring-white/20">
@else
    <span class="{{ $size }} {{ $text }} {{ $user?->avatarTone() ?? 'bg-slate-200 text-slate-700' }} inline-flex shrink-0 items-center justify-center rounded-full font-bold ring-2 ring-white/20">
        {{ $user?->initials() ?? '?' }}
    </span>
@endif
