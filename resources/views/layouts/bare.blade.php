{{-- Full-bleed pages that carry their own background and no shared chrome:
     the login/register screens and the printable receipt. --}}
@extends('layouts.app')

@push('styles')
<style>
    :root {
        --card:       rgba(255, 255, 255, 0.82);
        --card-solid: #ffffff;
    }
</style>
@endpush

@section('body')
@yield('content')
@endsection
