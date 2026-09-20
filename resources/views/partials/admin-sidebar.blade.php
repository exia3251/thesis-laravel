@php
    $user = auth()->user();

    // Only offer what this role may actually open, so nobody clicks into a
    // redirect. Mirrors the permission middleware on each route.
    $links = collect([
        ['url' => '/admin/dashboard', 'label' => 'Dashboard', 'can' => $user?->canViewFullDashboard()],
        ['url' => '/admin/analytics', 'label' => 'Analytics', 'can' => $user?->canViewFullDashboard()],
        ['url' => '/admin/products',  'label' => 'Products',  'can' => $user?->canManageProducts()],
        ['url' => '/admin/inventory', 'label' => 'Inventory', 'can' => $user?->canManageInventory()],
        ['url' => '/admin/sales',     'label' => 'Sales',     'can' => $user?->canViewSales()],
        ['url' => '/admin/reports',   'label' => 'Reports',   'can' => $user?->canViewReports()],
        ['url' => '/admin/users',     'label' => 'Users',     'can' => $user?->canManageUsers()],
    ])->where('can', true);
@endphp

<div class="fixed inset-y-0 left-0 w-64 bg-[var(--sidebar-bg)] shadow-2xl">
    <div class="flex h-20 items-center justify-center border-b border-white/10 bg-[linear-gradient(135deg,_rgba(20,138,103,0.15),_transparent)]">
        <div class="text-center">
            <div class="text-lg font-black tracking-tight leading-tight">
                <span style="color:#148a67;">RANEY</span><span style="color:#d9b14a;"> LUBRICANTS</span>
            </div>
            <div class="text-[10px] uppercase tracking-[0.28em] text-white/50 mt-0.5">Trading</div>
        </div>
    </div>

    @if ($user)
        <div class="mx-3 mt-4 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-3 py-3">
            @include('partials.avatar', ['user' => $user, 'size' => 'h-10 w-10', 'text' => 'text-sm'])
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-white">{{ $user->full_name }}</div>
                <div class="mt-0.5 text-[10px] uppercase tracking-[0.2em] text-[var(--accent)]">{{ $user->roleLabel() }}</div>
            </div>
        </div>
    @endif

    <nav class="mt-4 space-y-1 px-3">
        @foreach ($links as $link)
            <a href="{{ $link['url'] }}"
               @class([
                   'flex items-center rounded-2xl px-4 py-3',
                   'font-semibold text-white bg-[var(--sidebar-active)] ring-1 ring-white/10' => request()->is(ltrim($link['url'], '/')),
                   'text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white' => ! request()->is(ltrim($link['url'], '/')),
               ])>{{ $link['label'] }}</a>
        @endforeach
        <button type="button" onclick="logout()" class="w-full rounded-2xl px-4 py-3 text-left text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Logout</button>
    </nav>
</div>
