<?php

namespace Modules\Office\Filament\Resources\Offices\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Office\Models\Office;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Taxonomy\Vocabularies;

/**
 * Office form (FR-OFF-01/05/08/11, UI/UX Part A §5.2): name, overview, Head's message,
 * location and hours per language; type, parent office, short name, contact, own page or
 * not, pictures, address; people (Head, team); SEO. Sections, services and support contacts
 * are lists below the form (relation managers).
 */
class OfficeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("name.{$locale}")->label(__('office::office.fields.name'))->required($isDefault)->maxLength(255),
                        RichEditor::make("overview.{$locale}")->label(__('office::office.fields.overview'))->fileAttachments(false),
                        RichEditor::make("head_message.{$locale}")->label(__('office::office.fields.head_message'))->fileAttachments(false),
                        TextInput::make("location.{$locale}")->label(__('office::office.fields.location'))->maxLength(255),
                        Textarea::make("office_hours.{$locale}")->label(__('office::office.fields.office_hours'))->rows(2)->maxLength(500),
                    ]),
                ]),
                Section::make(__('office::office.fields.quick_links'))->description(__('office::office.help.quick_links'))
                    ->columnSpan(2)->compact()->collapsible()->schema([LinkListField::make('quick_links')->hiddenLabel()]),
                Section::make(__('office::office.fields.highlights'))->description(__('office::office.help.highlights'))
                    ->columnSpan(2)->compact()->collapsible()->collapsed()->schema([
                        Repeater::make('highlights')->hiddenLabel()->schema([
                            self::icon(),
                            ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                                TextInput::make("title.{$locale}")->label(__('office::office.fields.title')." ({$language})")->required($isDefault)->maxLength(100),
                                Textarea::make("text.{$locale}")->label(__('office::office.fields.description')." ({$language})")->rows(2)->maxLength(500),
                            ]),
                        ])->addActionLabel(__('office::office.actions.add_highlight'))->reorderable()->collapsible()->cloneable()->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => collect($state['title'] ?? [])->first(fn ($v) => filled($v))),
                    ]),
                Section::make(__('office::office.fields.quick_services'))->description(__('office::office.help.quick_services'))
                    ->columnSpan(2)->compact()->collapsible()->collapsed()->schema([
                        LinkListField::make('quick_services')->hiddenLabel()->schema([
                            Grid::make(3)->schema([
                                self::icon(),
                                ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                                    TextInput::make("label.{$locale}")->label(__('platform::relations.link_text')." ({$language})")->required($isDefault)->maxLength(100),
                                ]),
                                TextInput::make('url')->label(__('platform::relations.link_url'))->helperText(__('platform::relations.link_url_help'))
                                    ->required()->maxLength(2048)->regex('#^(https?://\S+|/\S*|mailto:\S+|tel:\S+)$#i'),
                            ]),
                        ])->addActionLabel(__('office::office.actions.add_quick_service')),
                    ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('office::office.sections.office'))->compact()->schema([
                        Select::make('type_term_id')->label(__('office::office.fields.type'))
                            ->options(fn (): array => app(Vocabularies::class)->options(Office::TYPE))->searchable(),
                        Select::make('parent_id')->label(__('office::office.fields.parent'))
                            ->options(fn (?Office $record): array => Office::query()->when($record?->exists, fn ($q) => $q->whereKeyNot($record->getKey()))
                                ->ordered()->get()->mapWithKeys(fn (Office $o): array => [$o->id => $o->name])->all())
                            ->searchable(),
                        TextInput::make('short_name')->label(__('office::office.fields.short_name'))->maxLength(50),
                        WebAddressField::forMicrosite(Office::class),
                        Toggle::make('has_microsite')->label(__('office::office.fields.has_microsite'))
                            ->helperText(__('office::office.help.has_microsite'))->default(true),
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
                    ]),
                    Section::make(__('office::office.sections.contact'))->compact()->schema([
                        TextInput::make('email')->label(__('office::office.fields.email'))->email()->maxLength(191),
                        TextInput::make('phone')->label(__('office::office.fields.phone'))->tel()->maxLength(50),
                        TextInput::make('extension')->label(__('office::office.fields.extension'))->maxLength(20),
                    ]),
                    Section::make(__('office::office.sections.pictures'))->compact()->schema([
                        MediaPicker::make('logo')->label(__('office::office.fields.logo'))->image(),
                        MediaPicker::make('cover')->label(__('office::office.fields.cover'))->image(),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
            PeopleManager::make()->columnSpanFull(),   // Head, team (FR-OFF-05)
        ]);
    }

    /** Icon name the theme draws; the usual ones are suggested */
    protected static function icon(): TextInput
    {
        return TextInput::make('icon')->label(__('office::office.fields.icon'))->helperText(__('office::office.help.icon'))->maxLength(50)
            ->datalist(['award', 'graduation-cap', 'user', 'users', 'archive', 'file-text', 'shield', 'target', 'eye', 'scale', 'briefcase', 'clipboard', 'calendar', 'book-open', 'building', 'phone', 'mail']);
    }
}
