@extends('layouts.app')

@section('logout-url', '/admin/logout')
@section('login-url', '/admin/login')

@push('styles')
<style>
    :root {
        --surface:        #f3f6f9;
        --card:           #ffffff;
        --sidebar-bg:     #0d1f18;
        --sidebar-hover:  rgba(20, 138, 103, 0.18);
        --sidebar-active: rgba(20, 138, 103, 0.28);
    }

    input, select, textarea {
        border-color: var(--line) !important;
    }

    /* The drawer states are written out rather than toggled as utility
       classes, so each one is a rule that can be read here instead of
       inferred from which class happens to be on the element. */
    #adminSidebar {
        transform: translateX(-100%);
        transition: transform .2s ease;
    }

    #adminSidebar[data-open="true"] {
        transform: translateX(0);
    }

    @media (min-width: 1024px) {
        #adminSidebar {
            transform: none;
        }
    }

    /* Wide tables are the one thing that cannot be made to fit a narrow
       screen by rearranging it, so they scroll sideways within their card
       rather than stretching the page. Applied here so every admin table
       gets it without each one remembering. */
    .admin-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .admin-table-wrap > table {
        min-width: 44rem;
    }

</style>
@endpush

@section('body')
<div class="min-h-screen bg-[var(--surface)]">

    {{-- Below lg the sidebar slides off the side rather than occupying it.
         It used to be fixed at every width, so on a phone it covered the
         screen while the content was pushed 16rem to the right of it. --}}
    <div id="adminSidebar" data-open="false" class="fixed inset-y-0 left-0 z-50 w-64">
        @include('partials.admin-sidebar')
    </div>

    @include('partials.admin-confirm')

    <div id="adminScrim" onclick="toggleAdminNav(false)"
         class="fixed inset-0 z-40 hidden bg-slate-900/50 lg:hidden"></div>

    <div class="lg:ml-64">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-[var(--line)] bg-white/90 px-4 backdrop-blur lg:hidden">
            <button type="button" onclick="toggleAdminNav(true)" aria-label="Open the menu"
                    class="rounded-xl p-2 text-[var(--ink)] transition hover:bg-[var(--surface)]">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
            <div class="text-sm font-black tracking-tight">
                <span class="text-[var(--primary)]">RANEY</span><span class="text-[var(--accent)]"> LUBRICANTS</span>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8">
            @yield('content')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleAdminNav(open) {
        document.getElementById('adminSidebar').dataset.open = open ? 'true' : 'false';
        document.getElementById('adminScrim').classList.toggle('hidden', !open);
        // Stops the page behind scrolling while the menu is over it.
        document.body.classList.toggle('overflow-hidden', open);
    }

    // Following a link should not leave the menu open behind the new page,
    // and coming back to a wide window should not leave the body locked.
    document.getElementById('adminSidebar').addEventListener('click', (event) => {
        if (event.target.closest('a')) toggleAdminNav(false);
    });

    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 1024px)').matches) toggleAdminNav(false);
    });
</script>
@endpush
