<?php

namespace Modules\Testimonials\Filament\Resources\Testimonials\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Testimonials\Filament\Resources\Testimonials\TestimonialResource;
use Modules\Testimonials\Models\Testimonial;
use Modules\Testimonials\Support\Authors;
use Platform\Filament\Components\PersonPicker;

class EditTestimonial extends EditRecord
{
    protected static string $resource = TestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Testimonial $record */
        $record = $this->getRecord();
        $author = $record->author();

        return $data + [
            'person' => $author?->person ? PersonPicker::keyOf($author->person) : null,
            'author_title' => $author?->getTranslations('role_label') ?? [],
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Testimonial $record */
        return DB::transaction(function () use ($record, $data): Testimonial {
            $record->update(Arr::except($data, ['person', 'author_title']));
            app(Authors::class)->set($record, PersonPicker::resolve($data['person'] ?? null), $data['author_title'] ?? null);

            return $record;
        });
    }
}
