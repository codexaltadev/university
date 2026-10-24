<?php

namespace Modules\International\Filament\Resources\Visitors\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\International\Filament\Resources\Visitors\VisitorResource;
use Modules\International\Support\VisitorTestimonial;

class EditVisitor extends EditRecord
{
    protected static string $resource = VisitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A visitor's words among the testimonials too (owner 2026-10-24); made once, then opened
            Action::make('asTestimonial')
                ->label(fn (): string => VisitorTestimonial::of($this->record) ? __('international::international.testimonial_open') : __('international::international.testimonial_make'))
                ->icon('heroicon-o-chat-bubble-left-right')->color('gray')
                ->visible(fn (): bool => VisitorTestimonial::available())
                ->requiresConfirmation(fn (): bool => VisitorTestimonial::of($this->record) === null)
                ->modalDescription(__('international::international.testimonial_help'))
                ->action(function (): void {
                    $made = VisitorTestimonial::of($this->record) === null;
                    $testimonial = VisitorTestimonial::make($this->record);
                    if ($made) {
                        Notification::make()->success()->title(__('international::international.testimonial_made'))->send();
                    }
                    $resource = 'Modules\\Testimonials\\Filament\\Resources\\Testimonials\\TestimonialResource';
                    $this->redirect($resource::getUrl('edit', ['record' => $testimonial]));
                }),
            DeleteAction::make(),
        ];
    }
}
