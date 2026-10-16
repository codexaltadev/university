<?php

namespace Modules\Testimonials\Filament\Resources\Testimonials\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Testimonials\Filament\Resources\Testimonials\TestimonialResource;
use Modules\Testimonials\Models\Testimonial;

class ListTestimonials extends ListRecords
{
    protected static string $resource = TestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('testimonials::testimonials.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Testimonial::PATH))->openUrlInNewTab(),
        ];
    }
}
