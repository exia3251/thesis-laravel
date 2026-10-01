@php
    use App\Models\PaymentRequest;

    $user = auth()->user();

    // Payments sitting unreviewed are the one thing in the back office that
    // goes stale if nobody looks, so the count rides on the nav item.
    $awaitingReview = $user?->canViewSales()
        ? PaymentRequest::where('status', 'processing')->count()
        : 0;

    // Only offer what this role may actually open, so nobody clicks into a
    // redirect. Mirrors the permission middleware on each route.
    $groups = [
        'Menu' => collect([
            ['url' => '/admin/dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'can' => $user?->canViewFullDashboard()],
            ['url' => '/admin/analytics', 'label' => 'Analytics', 'icon' => 'analytics', 'can' => $user?->canViewReports()],
            ['url' => '/admin/products',  'label' => 'Products',  'icon' => 'products',  'can' => $user?->canManageProducts()],
            ['url' => '/admin/inventory', 'label' => 'Inventory', 'icon' => 'inventory', 'can' => $user?->canManageInventory()],
            ['url' => '/admin/sales',     'label' => 'Sales',     'icon' => 'sales',     'can' => $user?->canViewSales(), 'badge' => $awaitingReview],
        ])->where('can', true),

        'General' => collect([
            ['url' => '/admin/users',     'label' => 'Users',     'icon' => 'users',     'can' => $user?->canManageUsers()],
            ['url' => '/admin/assistant', 'label' => 'Assistant', 'icon' => 'assistant', 'can' => $user?->canManageChatbot()],
            ['url' => '/admin/cms',       'label' => 'Shop content', 'icon' => 'content', 'can' => $user?->canManageContent()],
            ['url' => '/admin/backup',    'label' => 'Backup',    'icon' => 'backup',    'can' => $user?->canBackupDatabase()],
        ])->where('can', true),
    ];

    $icons = [
        'dashboard' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
        'analytics' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
        'products'  => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'inventory' => 'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
        'sales'     => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
        'users'     => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'assistant' => 'M8 10.5h8M8 14h5m-5 6.25-3.2 2.4A.75.75 0 0 1 3.6 22.1V18.5A4.5 4.5 0 0 1 2 15V7a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H8Z',
        'content'   => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 9.75h.008v.008H18V9.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3 19.5V6a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 6v13.5A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75Z',
        'backup'    => 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75',
        'logout'    => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75',
    ];
@endphp

{{-- Positioned by the layout, which slides it off the side on a narrow
     screen. It only has to fill whatever it is put in. --}}
<div class="flex h-full w-full flex-col bg-[var(--sidebar-bg)] shadow-2xl">
    <div class="relative flex h-20 shrink-0 items-center justify-center border-b border-white/10 bg-[linear-gradient(135deg,_rgba(20,138,103,0.15),_transparent)]">
        <button type="button" onclick="toggleAdminNav(false)" aria-label="Close the menu"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-white/50 transition hover:bg-white/10 hover:text-white lg:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
        <div class="text-center">
            <div class="text-lg font-black tracking-tight leading-tight">
                <span style="color:#148a67;">RANEY</span><span style="color:#d9b14a;"> LUBRICANTS</span>
            </div>
            <div class="text-[10px] uppercase tracking-[0.28em] text-white/50 mt-0.5">Trading</div>
        </div>
    </div>

    @if ($user)
        {{-- The card is the way into your own account, which is where
             people look for it. --}}
        <a href="/admin/account"
           @class([
               'mx-3 mt-4 flex shrink-0 items-center gap-3 rounded-2xl border px-3 py-3 transition',
               'border-white/20 bg-white/10' => request()->is('admin/account'),
               'border-white/10 bg-white/5 hover:bg-white/10' => ! request()->is('admin/account'),
           ])>
            @include('partials.avatar', ['user' => $user, 'size' => 'h-10 w-10', 'text' => 'text-sm'])
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-semibold text-white">{{ $user->full_name }}</div>
                <div class="mt-0.5 text-[10px] uppercase tracking-[0.2em] text-[var(--accent)]">{{ $user->roleLabel() }}</div>
            </div>
            <svg class="h-4 w-4 shrink-0 text-white/40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </a>
    @endif

    <nav class="mt-4 flex-1 overflow-y-auto px-3 pb-4">
        @foreach ($groups as $heading => $links)
            @continue($links->isEmpty())

            <div class="mt-4 mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.22em] text-white/45">{{ $heading }}</div>

            <div class="space-y-1">
                @foreach ($links as $link)
                    @php $active = request()->is(ltrim($link['url'], '/')); @endphp
                    <a href="{{ $link['url'] }}"
                       @class([
                           'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm',
                           'font-semibold text-white bg-[var(--sidebar-active)] ring-1 ring-white/10' => $active,
                           'text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white' => ! $active,
                       ])>
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$link['icon']] }}"/>
                        </svg>
                        <span class="flex-1">{{ $link['label'] }}</span>
                        @if (!empty($link['badge']))
                            <span class="inline-flex min-w-[1.4rem] items-center justify-center rounded-full bg-[var(--accent)] px-1.5 py-0.5 text-[10px] font-bold text-[#3d2f08]">{{ $link['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach

        <div class="mt-4 mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.22em] text-white/45">Session</div>
        <button type="button" onclick="logout(this)" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['logout'] }}"/>
            </svg>
            <span>Logout</span>
        </button>
    </nav>
</div>
