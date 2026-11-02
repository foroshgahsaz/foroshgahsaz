@include('filament.hooks.critical-admin-sidebar')
@php
    $adminAssetV = max(
        @filemtime(public_path('adminpanel/styles.css')) ?: 0,
        @filemtime(public_path('adminpanel/filament-overrides.css')) ?: 0,
        @filemtime(public_path('css/filament-admin-order.css')) ?: 0,
    );
@endphp
<link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
<link href="{{ asset('vendor/bootstrap/5.3.0/css/bootstrap.rtl.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/fontawesome/6.4.0/css/all.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('adminpanel/styles.css') }}?v={{ $adminAssetV }}">
@if (request()->routeIs('filament.admin.pages.dashboard'))
    @include('filament.hooks.dashboard-map-styles')
@endif
@if (request()->routeIs('filament.representative.pages.dashboard'))
    <link rel="stylesheet" href="{{ asset('css/rep-order-wizard.css') }}?v={{ @filemtime(public_path('css/rep-order-wizard.css')) ?: 0 }}">
@endif
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/11/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('adminpanel/filament-overrides.css') }}?v={{ $adminAssetV }}">
<link rel="stylesheet" href="{{ asset('css/filament-admin-order.css') }}">
@if (request()->routeIs([
    'filament.admin.resources.products.index',
    'filament.admin.resources.payments.index',
]))
    <link rel="stylesheet" href="{{ asset('css/admin-product-list.css') }}?v={{ @filemtime(public_path('css/admin-product-list.css')) ?: 0 }}">
@endif
