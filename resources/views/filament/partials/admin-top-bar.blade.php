@php
    use App\Filament\Resources\UserResource;

    $user = filament()->auth()->user();
    $profileUrl = filament()->getUrl();
    if ($user !== null && UserResource::canEdit($user)) {
        $profileUrl = UserResource::getUrl('edit', ['record' => $user], panel: 'admin');
    }

    $initialCreate = $adminTopBarCreate ?? null;
    $utilityLink = $adminTopBarUtilityLink ?? null;
@endphp

<div class="admin-top-bar"
     role="banner"
     x-data="{ create: @js($initialCreate), utilityLink: @js($utilityLink) }"
     @admin-top-bar-create.window="create = $event.detail.create ?? null"
     @admin-top-bar-utility-link.window="utilityLink = $event.detail.link ?? null">
    <div class="admin-top-bar__primary">
        <template x-if="create && create.url">
            <a :href="create.url"
               class="admin-top-bar__create-btn">
                <i class="fas fa-plus" aria-hidden="true"></i>
                <span x-text="create.label"></span>
            </a>
        </template>
    </div>

    <div class="admin-top-bar__spacer" aria-hidden="true"></div>

    <div class="admin-top-bar__utilities">
        <a href="{{ $profileUrl }}"
           class="admin-top-bar__icon-btn admin-top-bar__icon-btn--profile"
           title="حساب کاربری"
           aria-label="حساب کاربری">
            <i class="far fa-user" aria-hidden="true"></i>
        </a>
        <button type="button"
                class="admin-top-bar__icon-btn"
                aria-label="اعلان‌ها"
                title="اعلان‌ها"
                disabled>
            <i class="far fa-bell" aria-hidden="true"></i>
        </button>
        <span class="admin-top-bar__vsep" aria-hidden="true"></span>
        <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="admin-top-bar__logout-form">
            @csrf
            <button type="submit"
                    class="admin-top-bar__icon-btn admin-top-bar__icon-btn--logout"
                    title="خروج"
                    aria-label="خروج">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
            </button>
        </form>
        <template x-if="utilityLink && utilityLink.url">
            <a :href="utilityLink.url"
               target="_blank"
               rel="noopener"
               class="admin-top-bar__create-btn">
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                <span x-text="utilityLink.label || 'مشاهده'"></span>
            </a>
        </template>
        <a href="{{ url('/') }}"
           target="_blank"
           rel="noopener"
           class="fi-admin-global-btn fi-admin-global-btn-store fi-admin-global-btn-store--outline">
            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
            مشاهده فروشگاه
        </a>
    </div>
</div>
