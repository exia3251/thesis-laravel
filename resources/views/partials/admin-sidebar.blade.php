@php
    $links = [
        ['url' => '/admin/dashboard', 'label' => 'Dashboard'],
        ['url' => '/admin/products',  'label' => 'Products'],
        ['url' => '/admin/inventory', 'label' => 'Inventory'],
        ['url' => '/admin/sales',     'label' => 'Sales'],
        ['url' => '/admin/reports',   'label' => 'Reports'],
    ];

    if (auth()->check() && auth()->user()->isSuperAdmin()) {
        $links[] = ['url' => '/admin/users', 'label' => 'Users'];
    }
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
    <nav class="mt-6 space-y-1 px-3">
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
