<?php

namespace App\Filament\Support\Concerns;

use App\Filament\Support\AdminListHeader;
use App\Filament\Support\AdminTopBarCreateResolver;
use Filament\Resources\Resource;

trait SyncsAdminTopBarCreate
{
    public function bootSyncsAdminTopBarCreate(): void
    {
        if (! $this instanceof \App\Filament\Resources\Pages\AdminListRecords) {
            return;
        }

        $this->syncAdminTopBarCreate();
    }

    /**
     * @return array{url: string, label: string}|null
     */
    protected function resolveAdminTopBarCreatePayload(): ?array
    {
        return AdminTopBarCreateResolver::resolveForPage(static::class);
    }

    protected function syncAdminTopBarCreate(): void
    {
        $create = $this->resolveAdminTopBarCreatePayload();

        view()->share('adminTopBarCreate', $create);

        $encoded = json_encode($create, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->js(<<<JS
            window.dispatchEvent(new CustomEvent('admin-top-bar-create', { detail: { create: {$encoded} } }));
        JS);
    }

    protected function clearAdminTopBarCreate(): void
    {
        view()->share('adminTopBarCreate', null);

        $this->js(<<<'JS'
            window.dispatchEvent(new CustomEvent('admin-top-bar-create', { detail: { create: null } }));
        JS);
    }

    /** @param  array{url: string, label: string}|null  $link */
    protected function syncAdminTopBarUtilityLink(?array $link): void
    {
        view()->share('adminTopBarUtilityLink', $link);

        $encoded = json_encode($link, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->js(<<<JS
            window.dispatchEvent(new CustomEvent('admin-top-bar-utility-link', { detail: { link: {$encoded} } }));
        JS);
    }

    protected function clearAdminTopBarUtilityLink(): void
    {
        view()->share('adminTopBarUtilityLink', null);

        $this->js(<<<'JS'
            window.dispatchEvent(new CustomEvent('admin-top-bar-utility-link', { detail: { link: null } }));
        JS);
    }
}
