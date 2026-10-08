<?php

namespace Modules\Faq\Filament\Resources\Faqs\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Faq\Filament\Resources\Faqs\FaqResource;

class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;
}
