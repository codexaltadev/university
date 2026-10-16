<?php

namespace Modules\Testimonials\Filament\Resources\Testimonials\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Testimonials\Filament\Resources\Testimonials\TestimonialResource;
use Modules\Testimonials\Models\Testimonial;
use Modules\Testimonials\Support\Authors;
use Platform\Filament\Components\PersonPicker;

class CreateTestimonial extends CreateRecord
{
    protected static string $resource = TestimonialResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Testimonial {
            $testimonial = Testimonial::query()->create(Arr::except($data, ['person', 'author_title']));
            app(Authors::class)->set($testimonial, PersonPicker::resolve($data['person'] ?? null), $data['author_title'] ?? null);

            return $testimonial;
        });
    }
}
