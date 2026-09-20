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
</style>
@endpush

@section('body')
<div class="min-h-screen bg-[var(--surface)]">
    @include('partials.admin-sidebar')

    <div class="ml-64 p-8">
        @yield('content')
    </div>
</div>
@endsection
