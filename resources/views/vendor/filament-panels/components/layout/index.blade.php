@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div @class([
        'fi-admin-shell',
        'fi-admin-shell--representative' => request()->routeIs('filament.representative.*'),
    ]) id="fiAdminShell">
        @if (request()->routeIs('filament.representative.*'))
            @include('filament.partials.representative-sidebar')
        @else
            @include('filament.partials.admin-sidebar')
        @endif

        <div class="sidebar-backdrop" id="sidebarBackdrop" hidden aria-hidden="true"></div>

        <div class="main-content" id="mainContent">
            @unless (request()->routeIs('filament.representative.*'))
                @include('filament.partials.admin-top-bar', [
                    'adminTopBarCreate' => view()->shared('adminTopBarCreate'),
                    'adminTopBarUtilityLink' => view()->shared('adminTopBarUtilityLink'),
                ])
            @endunless
            <div class="content-area">
                <main class="fi-main mx-auto w-full">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>
