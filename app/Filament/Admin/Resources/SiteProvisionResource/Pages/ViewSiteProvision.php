<?php

namespace App\Filament\Admin\Resources\SiteProvisionResource\Pages;

use App\Filament\Admin\Resources\SiteProvisionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSiteProvision extends ViewRecord
{
    protected static string $resource = SiteProvisionResource::class;

    protected static string $view = 'filament.admin.resources.site-provision.ver';

    public function getTitle(): string
    {
        return $this->record->dominio;
    }
}
