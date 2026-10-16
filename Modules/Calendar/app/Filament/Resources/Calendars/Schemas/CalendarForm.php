<?php

namespace Modules\Calendar\Filament\Resources\Calendars\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Modules\Calendar\Models\Calendar;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;

/**
 * A calendar (DB-03 §12 v1.6): title and duration per language on the left; session, year,
 * the faculties it is for (none = every faculty), homepage, active, address, hard copy and
 * SEO on the right. Its dates are edited below the form.
 */
class CalendarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("title.{$locale}")->label(__('calendar::calendar.fields.title'))->required($isDefault)->maxLength(255)
                            ->helperText($isDefault ? __('calendar::calendar.help.title') : null),
                        TextInput::make("duration_label.{$locale}")->label(__('calendar::calendar.fields.duration_label'))
                            ->helperText(__('calendar::calendar.help.duration_label'))->maxLength(100),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('session_label')->label(__('calendar::calendar.fields.session_label'))
                            ->helperText(__('calendar::calendar.help.session_label'))->maxLength(50),
                        TextInput::make('year')->label(__('calendar::calendar.fields.year'))->required()->integer()
                            ->minValue(2000)->maxValue(2100)->default((int) date('Y')),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('calendar::calendar.sections.calendar'))->compact()->schema([
                        Select::make('faculties')->label(__('calendar::calendar.fields.faculties'))
                            ->helperText(__('calendar::calendar.help.faculties'))
                            ->multiple()->searchable()
                            ->options(fn (): array => static::facultyOptions())
                            ->visible(fn (): bool => class_exists(static::FACULTY))
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Select $component, ?Model $record): void {
                                if ($record instanceof Calendar && $record->exists) {
                                    $component->state($record->facultyIds());
                                }
                            })
                            ->saveRelationshipsUsing(fn (Calendar $record, ?array $state) => $record->syncFaculties(array_map('intval', $state ?? []))),
                        WebAddressField::make(prefix: '/'.Calendar::PATH.'/'),
                        Toggle::make('show_on_home')->label(__('calendar::calendar.fields.show_on_home'))
                            ->helperText(__('calendar::calendar.help.show_on_home'))->default(true),
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
                    ]),
                    Section::make(__('calendar::calendar.sections.media'))->compact()->schema([
                        MediaPicker::make('hard_copy')->label(__('calendar::calendar.fields.hard_copy'))
                            ->helperText(__('calendar::calendar.help.hard_copy')),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public const FACULTY = 'Modules\\Faculty\\Models\\Faculty';

    /** @return array<int, string> */
    protected static function facultyOptions(): array
    {
        $class = static::FACULTY;

        return class_exists($class)
            ? $class::query()->ordered()->get()->mapWithKeys(fn ($f): array => [$f->id => $f->short_name ? "{$f->short_name} — {$f->name}" : $f->name])->all()
            : [];
    }
}
