<?php

namespace Modules\Seo\Filament\Resources\Redirects\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Filament\Resources\Redirects\RedirectResource;
use Modules\Seo\Support\Redirects;

class CreateRedirect extends CreateRecord
{
    protected static string $resource = RedirectResource::class;

    /** Through Redirects::save: chains resolved, older redirects to this address re-pointed. */
    protected function handleRecordCreation(array $data): Model
    {
        return app(Redirects::class)->save([...$data, 'source' => 'manual']);
    }
}
