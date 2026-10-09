<?php

namespace Modules\Seo\Filament\Resources\Redirects\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Filament\Resources\Redirects\RedirectResource;
use Modules\Seo\Models\Redirect;
use Modules\Seo\Support\Redirects;

class EditRedirect extends EditRecord
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** @param Redirect $record */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(Redirects::class)->save($data, $record);
    }
}
