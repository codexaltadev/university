<?php

namespace Modules\Taxonomy\Filament\Resources\Vocabularies\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Taxonomy\Filament\Resources\Vocabularies\VocabularyResource;
use Platform\Taxonomy\Vocabularies;

class ListVocabularies extends ListRecords
{
    protected static string $resource = VocabularyResource::class;

    /** A newly activated module's vocabularies appear on the first visit. */
    public function mount(): void
    {
        app(Vocabularies::class)->sync();

        parent::mount();
    }
}
