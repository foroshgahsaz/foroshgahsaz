<?php

namespace App\Filament\Support\Concerns;

trait ClearsAdminTopBarCreate
{
    use SyncsAdminTopBarCreate;

    public function bootClearsAdminTopBarCreate(): void
    {
        $this->clearAdminTopBarCreate();
        $this->clearAdminTopBarUtilityLink();
    }
}
