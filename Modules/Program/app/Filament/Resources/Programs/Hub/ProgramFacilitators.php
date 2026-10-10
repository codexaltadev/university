<?php

namespace Modules\Program\Filament\Resources\Programs\Hub;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Modules\Program\Models\ProgramFacilitator;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;

/** Facilitators / Industry Experts (FR-PROG-06). */
class ProgramFacilitators extends ProgramItemsPage
{
    protected static string $relationship = 'facilitators';

    protected static ?int $navigationSort = 34;

    public static function hubKey(): string
    {
        return 'facilitators';
    }

    public static function getNavigationLabel(): string
    {
        return __('program::program.items.facilitators');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label(__('program::program.fields.person_name'))->required()->maxLength(191),
            Select::make('type')->label(__('program::program.fields.facilitator_type'))
                ->options(collect(ProgramFacilitator::TYPES)->mapWithKeys(fn (string $t): array => [$t => __("program::program.facilitator_types.{$t}")])->all()),
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale): array => [
                TextInput::make("designation.{$locale}")->label(__('program::program.fields.designation'))->maxLength(255),
                TextInput::make("organization.{$locale}")->label(__('program::program.fields.organization'))->maxLength(255),
            ]),
            TextInput::make('email')->label(__('program::program.fields.email'))->email()->maxLength(191),
            TextInput::make('phone')->label(__('program::program.fields.phone'))->tel()->maxLength(50),
            TextInput::make('profile_url')->label(__('program::program.fields.profile_url'))->url()->maxLength(2048),
            TextInput::make('linkedin_url')->label(__('program::program.fields.linkedin_url'))->url()->maxLength(2048),
            MediaPicker::make('photo')->label(__('program::program.fields.photo'))->image(),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('program::program.fields.person_name'))->searchable()
                ->description(fn (ProgramFacilitator $record): ?string => collect([$record->designation, $record->organization])->filter()->implode(', ') ?: null),
            TextColumn::make('type')->label(__('program::program.fields.facilitator_type'))
                ->formatStateUsing(fn (?string $state): ?string => $state ? __("program::program.facilitator_types.{$state}") : null),
        ];
    }
}
