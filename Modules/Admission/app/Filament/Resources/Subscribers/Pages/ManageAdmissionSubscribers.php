<?php

namespace Modules\Admission\Filament\Resources\Subscribers\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Admission\Filament\Resources\Subscribers\AdmissionSubscriberResource;
use Modules\Admission\Models\AdmissionSubscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Subscribers come from the website form; here they are found, exported (FR-ADM-12) and unsubscribed. */
class ManageAdmissionSubscribers extends ManageRecords
{
    protected static string $resource = AdmissionSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label(__('admission::admission.export'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['Channel', 'Contact', 'Topic', 'Source', 'Subscribed', 'Verified']);
                    AdmissionSubscriber::query()->subscribed()->orderBy('id')->chunk(1000, function ($rows) use ($out): void {
                        foreach ($rows as $s) {
                            fputcsv($out, [$s->channel, $s->contact, $s->topic, $s->source, $s->created_at?->toDateString(), $s->verified_at?->toDateString()]);
                        }
                    });
                    fclose($out);
                }, 'admission-subscribers.csv', ['Content-Type' => 'text/csv'])),
        ];
    }
}
