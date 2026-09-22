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

    @include('partials.verify-banner')

    @yield('content')

    @includeWhen($showFooter ?? true, 'partials.customer-footer')

    {{-- One include for every storefront page, so the conversation is there
         wherever the visitor happens to be. --}}
    @include('partials.chat-widget')
</div>
@endsection
