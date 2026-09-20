@extends('layouts.app')

@section('logout-url', '/shop/logout')
@section('login-url', '/shop/login')

@push('styles')
<style>
    :root {
        --card:       rgba(255, 255, 255, 0.82);
        --card-solid: #ffffff;
    }
</style>
@endpush

@section('body')
<div class="min-h-screen bg-[var(--surface)]">
    @includeWhen($showHeader ?? true, 'partials.customer-header', ['showSearch' => $showSearch ?? false])

    @yield('content')

    @includeWhen($showFooter ?? true, 'partials.customer-footer')
</div>
@endsection
