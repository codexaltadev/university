<?php

namespace Modules\Faculty\Filament\Resources\Faculties\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;
use Modules\Faculty\Models\Faculty;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Filament\Hub\EntityFormPart;

/**
 * Faculty form (FR-FAC-01/03, UI/UX Part A §5.2): name, overview, Dean's message per
 * language; short name, contact, pictures; people (Dean …); SEO. A Faculty user
 * (scoped) edits the profile but not the name, short name or web address (BRL-FAC-02).
 */
class FacultyForm
{
    public static function configure(Schema $schema): Schema
    {
        $profileOnly = fn (): bool => static::profileOnly();

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("name.{$locale}")->label(__('faculty::faculty.fields.name'))
                            ->required($isDefault)->maxLength(255)->disabled($profileOnly),
                        RichEditor::make("overview.{$locale}")->label(__('faculty::faculty.fields.overview'))->fileAttachments(false),
                        RichEditor::make("dean_message.{$locale}")->label(__('faculty::faculty.fields.dean_message'))->fileAttachments(false),
                        TextInput::make("location.{$locale}")->label(__('faculty::faculty.fields.location'))->maxLength(255),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('faculty::faculty.sections.faculty'))->compact()->schema([
                        TextInput::make('short_name')->label(__('faculty::faculty.fields.short_name'))
                            ->required()->maxLength(30)->disabled($profileOnly)
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at')),
                        WebAddressField::make(prefix: '/faculty/')->disabled($profileOnly),
                        TextInput::make('email')->label(__('faculty::faculty.fields.email'))->email()->maxLength(191),
                        TextInput::make('phone')->label(__('faculty::faculty.fields.phone'))->tel()->maxLength(50),
                        Toggle::make('show_in_listing')->label(__('faculty::faculty.fields.show_in_listing'))
                            ->helperText(__('faculty::faculty.help.show_in_listing'))->default(true),
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->disabled($profileOnly),
                    ]),
                    Section::make(__('faculty::faculty.sections.pictures'))->compact()->schema([
                        MediaPicker::make('logo')->label(__('faculty::faculty.fields.logo'))->image(),
                        MediaPicker::make('photo')->label(__('faculty::faculty.fields.photo'))->image(),
                        MediaPicker::make('cover')->label(__('faculty::faculty.fields.cover'))->image(),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
            PeopleManager::make()->columnSpanFull(),   // Dean, Associate Dean, Officer, Contact (FR-FAC-03)
            ...EntityFormPart::sectionsFor(Faculty::class),   // other modules' read-only parts (UI/UX Part A §5.2.1)
        ]);
    }

    /** BRL-FAC-02: users with a scope edit the profile only. */
    public static function profileOnly(): bool
    {
        $user = Auth::user();

        return $user !== null && ! $user->isSuperAdmin() && $user->scopes()->exists();
    }
}
