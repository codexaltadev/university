<?php

namespace Modules\Administration\Filament\Resources\Members\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Modules\Administration\Filament\Resources\Categories\AdministrationCategoryResource;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use Platform\Assignments\AssignmentEngine;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;

/**
 * A member (DB-04 §4.2 v1.2): name, designation and biography per language on the left with
 * contact and what is public; category, office, employee ID, photo, active and the places they
 * are assigned on the right. Changing a phone here changes it on every place (AC-ADMN-01).
 */
class MemberForm
{
    protected const OFFICE = 'Modules\\Office\\Models\\Office';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("name.{$locale}")->label(__('administration::administration.fields.name'))->required($isDefault)->maxLength(150),
                            TextInput::make("designation.{$locale}")->label(__('administration::administration.fields.designation'))
                                ->helperText($isDefault ? __('administration::administration.help.designation') : null)->maxLength(191),
                            RichEditor::make("biography.{$locale}")->label(__('administration::administration.fields.biography'))->fileAttachments(false),
                        ]),
                    ]),
                    Section::make(__('administration::administration.sections.contact'))
                        ->description(__('administration::administration.help.contact'))->compact()->schema([
                            Grid::make(3)->schema([
                                TextInput::make('email')->label(__('administration::administration.fields.email'))->email()->maxLength(191),
                                TextInput::make('phone')->label(__('administration::administration.fields.phone'))->tel()->maxLength(50),
                                TextInput::make('extension')->label(__('administration::administration.fields.extension'))->maxLength(20),
                                Toggle::make('show_email')->label(__('administration::administration.fields.public'))->default(true),
                                Toggle::make('show_phone')->label(__('administration::administration.fields.public'))->default(true),
                                Toggle::make('show_extension')->label(__('administration::administration.fields.public'))->default(true),
                            ]),
                        ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('administration::administration.sections.member'))->compact()->schema([
                        Select::make('category_id')->label(__('administration::administration.category.singular'))->required()
                            ->options(fn (): array => AdministrationCategory::query()->ordered()->get()
                                ->mapWithKeys(fn (AdministrationCategory $c): array => [$c->id => $c->name.($c->is_public ? '' : ' ('.__('administration::administration.not_public').')')])->all())
                            ->helperText(fn (): HtmlString => new HtmlString('<a href="'.AdministrationCategoryResource::getUrl().'" class="underline">'.e(__('administration::administration.category.manage')).'</a>'))
                            ->searchable(),
                        Select::make('primary_office_id')->label(__('administration::administration.fields.primary_office'))
                            ->options(fn (): array => class_exists(self::OFFICE) ? (self::OFFICE)::query()->ordered()->get()->mapWithKeys(fn ($o): array => [$o->id => $o->name])->all() : [])
                            ->visible(fn (): bool => class_exists(self::OFFICE))
                            ->searchable(),
                        TextInput::make('employee_id')->label(__('administration::administration.fields.employee_id'))->maxLength(30),
                        WebAddressField::make(prefix: '/'.Member::PATH.'/'),
                        Toggle::make('is_active')->label(__('platform::relations.active'))
                            ->helperText(__('administration::administration.help.is_active'))->default(true),
                    ]),
                    Section::make(__('administration::administration.fields.photo'))->compact()->schema([
                        MediaPicker::make('photo')->hiddenLabel()->image(),
                    ]),
                    Section::make(__('administration::administration.sections.places'))->compact()->schema([
                        Placeholder::make('places')->hiddenLabel()
                            ->content(fn (?Member $record): string => static::places($record)),
                    ])->visibleOn('edit'),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    /** "Registrar Office — Deputy Registrar; Syndicate — Member Secretary" (AC-ADMN-03), read only. */
    protected static function places(?Member $record): string
    {
        $list = $record ? app(AssignmentEngine::class)->assignmentsOf($record)->load('assignable') : collect();
        if ($list->isEmpty()) {
            return __('administration::administration.no_places');
        }

        return $list->map(fn ($a): string => trim((string) ($a->assignable?->name ?? $a->assignable?->title ?? $a->assignable_type).' — '.($a->role_label ?: $a->role_key)))->implode('; ');
    }
}
