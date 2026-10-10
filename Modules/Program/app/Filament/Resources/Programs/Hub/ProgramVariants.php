<?php

namespace Modules\Program\Filament\Resources\Programs\Hub;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramVariant;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Scope\UserScopeFilter;

/** Program Variants, e.g. Dual Degree or Evening (FR-PROG-09). */
class ProgramVariants extends ProgramItemsPage
{
    protected static string $relationship = 'variants';

    protected static ?int $navigationSort = 37;

    public static function hubKey(): string
    {
        return 'options';
    }

    public static function getNavigationLabel(): string
    {
        return __('program::program.items.variants');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("title.{$locale}")->label(__('program::program.fields.title'))->required($isDefault)->maxLength(255),
                Textarea::make("short_description.{$locale}")->label(__('program::program.fields.short_description'))->required($isDefault)->rows(2)->maxLength(2000),
                RichEditor::make("details.{$locale}")->label(__('program::program.fields.details'))->fileAttachments(false),
                TextInput::make("button_text.{$locale}")->label(__('program::program.fields.button_text'))->maxLength(100),
            ]),
            Select::make('related_program_id')->label(__('program::program.fields.related_program'))->searchable()
                ->options(fn (): array => $this->programOptions()),
            TextInput::make('button_url')->label(__('program::program.fields.button_url'))->url()->maxLength(2048)
                ->helperText(__('program::program.help.button_url')),
            MediaPicker::make('image')->label(__('program::program.fields.image'))->image(),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('title')->label(__('program::program.fields.title'))
                ->description(fn (ProgramVariant $record): ?string => $record->relatedProgram?->short_name),
        ];
    }

    /**
     * Any other program may be linked, also outside the user's scope (only its name is used).
     *
     * @return array<int, string>
     */
    protected function programOptions(): array
    {
        return UserScopeFilter::withoutScope(fn () => Program::query()->whereKeyNot($this->getOwnerRecord()->getKey())->ordered()->get())
            ->mapWithKeys(fn (Program $p): array => [$p->id => "{$p->short_name} — {$p->name}"])->all();
    }
}
