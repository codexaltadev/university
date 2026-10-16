<?php

namespace Modules\Slider\Filament\Resources\Slides\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Slider\Filament\Resources\Slides\SlideResource;

class EditSlide extends EditRecord
{
    protected static string $resource = SlideResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
