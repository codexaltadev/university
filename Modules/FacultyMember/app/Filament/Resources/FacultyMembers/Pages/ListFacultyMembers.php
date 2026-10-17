<?php

namespace Modules\FacultyMember\Filament\Resources\FacultyMembers\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Modules\FacultyMember\Filament\Resources\FacultyMembers\FacultyMemberResource;
use Modules\FacultyMember\Models\FacultyMember;
use Modules\FacultyMember\Sync\FacultyMemberSync;
use Modules\FacultyMember\Sync\SyncResult;

/** The teachers, the last sync's result above them, and "Sync now" (FR-FM-08). */
class ListFacultyMembers extends ListRecords
{
    protected static string $resource = FacultyMemberResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $last = FacultyMemberSync::lastResult();
        if ($last === null) {
            return __('facultymember::facultymember.sync.never');
        }

        $when = Carbon::parse($last['at'])->diffForHumans();
        if ($last['failure']) {
            return new HtmlString('<span style="color:rgb(220 38 38)">'.e(__('facultymember::facultymember.sync.last_failed', ['when' => $when, 'why' => $last['failure']])).'</span>');
        }

        $text = __('facultymember::facultymember.sync.last', ['when' => $when, 'source' => (string) $last['source']] + SyncResult::counts($last));
        if ($last['unmatched_departments'] !== []) {
            $text .= ' '.__('facultymember::facultymember.sync.unmatched', ['list' => implode(', ', $last['unmatched_departments'])]);
        }
        if ($last['errors'] !== []) {
            $text .= ' '.trans_choice('facultymember::facultymember.sync.with_errors', count($last['errors']), ['count' => count($last['errors'])]);
        }

        return $text;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')->label(__('facultymember::facultymember.sync.action'))->icon(Heroicon::OutlinedArrowPath)
                ->authorize(fn (): bool => (bool) auth()->user()?->can('sync', FacultyMember::class))
                ->requiresConfirmation()->modalDescription(__('facultymember::facultymember.sync.confirm'))
                ->action(function (): void {
                    $result = app(FacultyMemberSync::class)->run();

                    if (! $result->ok()) {
                        Notification::make()->danger()->title($result->failure)->body(__('facultymember::facultymember.sync.nothing_removed'))->persistent()->send();

                        return;
                    }
                    Notification::make()->{$result->errors === [] && $result->unmatchedDepartments === [] ? 'success' : 'warning'}()
                        ->title(__('facultymember::facultymember.sync.done', SyncResult::counts($result->toArray())))
                        ->body(implode("\n", array_filter([
                            $result->unmatchedDepartments !== [] ? __('facultymember::facultymember.sync.unmatched', ['list' => implode(', ', $result->unmatchedDepartments)]) : null,
                            ...array_slice($result->errors, 0, 10),
                        ])))
                        ->persistent($result->errors !== [] || $result->unmatchedDepartments !== [])
                        ->send();
                }),
            Action::make('website')->label(__('facultymember::facultymember.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.FacultyMember::PATH))->openUrlInNewTab(),
        ];
    }
}
