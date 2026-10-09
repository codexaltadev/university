<?php

namespace Modules\ActivityLog\Filament\Resources\Activities\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Platform\Activity\Activity;

/** One entry: who, what, where from, and old → new values (FR-LOG-04). */
class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->columnSpanFull()->schema([
                TextEntry::make('created_at')->label(__('activitylog::activity.fields.when'))->dateTime(),
                TextEntry::make('causer.name')->label(__('activitylog::activity.fields.who'))->placeholder(__('activitylog::activity.system')),
                TextEntry::make('event')->label(__('activitylog::activity.fields.what'))->badge(),
                TextEntry::make('log_name')->label(__('activitylog::activity.fields.log')),
                TextEntry::make('module_slug')->label(__('activitylog::activity.fields.module'))->placeholder('—'),
                TextEntry::make('subject')->label(__('activitylog::activity.fields.record'))
                    ->state(fn (Activity $record): string => $record->subject_type ? "{$record->subject_type} #{$record->subject_id}" : '—'),
                TextEntry::make('ip_address')->label('IP')->placeholder('—'),
                TextEntry::make('user_agent')->label(__('activitylog::activity.fields.browser'))->placeholder('—')->columnSpan(2),
            ]),
            Section::make(__('activitylog::activity.fields.changes'))->columnSpanFull()->schema([
                ViewEntry::make('attribute_changes')->hiddenLabel()->view('activitylog::changes-entry'),
            ]),
            Section::make(__('activitylog::activity.fields.details'))->collapsible()->collapsed()->columnSpanFull()
                ->visible(fn (Activity $record): bool => filled($record->properties?->all()))
                ->schema([
                    KeyValueEntry::make('properties')->hiddenLabel()
                        ->state(fn (Activity $record): array => collect($record->properties?->all() ?? [])->map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v))->all()),
                ]),
        ]);
    }
}
