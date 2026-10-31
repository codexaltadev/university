<?php

namespace Modules\Page\Filament\Resources\Pages\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Modules\Page\Blocks\PageBlocks;
use Modules\Page\Models\Page;
use Platform\Context\UrlRules;
use Platform\Context\WorkingIn;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Filament\Tables\ContentFilters;
use Platform\Localization\Languages;
use Platform\Theme\SiteData;

/** Standard content form (UI/UX Part A §5.2): blocks per language left; publishing and page settings right. */
class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("title.{$locale}")->label(__('page::page.fields.title'))->required($isDefault)->maxLength(255),
                        Textarea::make("excerpt.{$locale}")->label(__('page::page.fields.excerpt'))->rows(2)->maxLength(500),
                        PageBlocks::builder("content.{$locale}")->label(__('page::page.fields.content')),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make(__('page::page.sections.page'))->compact()->schema([
                        // FR-PAGE-05: main site (unscoped users) or one of the user's entities
                        Select::make('owner_key')->label(__('platform::relations.owner'))
                            ->options(fn (): array => ContentFilters::ownerOptions())
                            ->default(fn (): ?string => WorkingIn::pick(ContentFilters::ownerKeys()))   // "Working in", else the main site or the user's first entity
                            ->required()->searchable()->live()
                            ->in(fn (): array => ContentFilters::ownerKeys())
                            ->afterStateHydrated(fn (Select $component, ?Page $record) => $record?->exists ? $component->state($record->ownerKey()) : null),
                        // FR-PAGE-03: pages of the same owner; never under itself
                        Select::make('parent_id')->label(__('page::page.fields.parent'))
                            ->options(fn (Get $get, ?Page $record): array => static::parentOptions($get('owner_key'), $record))
                            ->placeholder(__('page::page.top_level'))
                            ->searchable(),
                        Select::make('template')->label(__('page::page.fields.template'))
                            ->options(collect(Page::TEMPLATES)->mapWithKeys(fn (string $t): array => [$t => __("page::page.templates.{$t}")])->all())
                            ->default('default')->required()->selectablePlaceholder(false)->live(),
                        // DB-02 v0.2 §8.1: a free menu of the same owner in the sidebar, left or right (Admission, DLRC …); none = the parent's
                        Select::make('sidebar_menu_id')->label(__('page::page.fields.sidebar_menu'))
                            ->options(fn (Get $get): array => filled($get('owner_key')) ? (array) app(SiteData::class)->get('free-menus', $get('owner_key')) : [])
                            ->placeholder(__('page::page.sidebar_inherit'))
                            ->helperText(__('page::page.help.sidebar_menu'))
                            ->visible(fn (Get $get): bool => in_array($get('template'), Page::SIDEBAR_TEMPLATES, true) && app(SiteData::class)->has('free-menus')),
                        // DB-02 v0.3: "Quick Links" under the sidebar menu (DIU's /admission)
                        LinkListField::make('quick_links')->label(__('page::page.fields.quick_links'))
                            ->visible(fn (Get $get): bool => in_array($get('template'), Page::SIDEBAR_TEMPLATES, true)),
                        WebAddressField::make(prefix: fn (Get $get, ?Page $record): string => static::addressPrefix($get, $record))
                            ->rules([fn (Get $get, ?Page $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                // FR-GEN-35: a top-level page's address may not be an entity's under the same owner
                                if (filled($value) && blank($get('parent_id')) && ($record === null || Str::slug($value) !== $record->slug(app(Languages::class)->default()))
                                    && ($message = UrlRules::pageClash($get('owner_key') ?: 'site:0', (string) $value))) {
                                    $fail($message);
                                }
                                // a main site page inside another may not take a module page's address ("/admission/result")
                                if (filled($value) && filled($get('parent_id')) && ($get('owner_key') ?: 'site:0') === 'site:0'
                                    && ($message = UrlRules::modulePageClash(static::addressPrefix($get, $record).Str::slug((string) $value)))) {
                                    $fail($message);
                                }
                            }]),
                    ]),
                    Section::make(__('page::page.sections.files'))->compact()->schema([
                        MediaPicker::make('featured_image')->label(__('page::page.fields.featured_image'))->image(),
                        MediaPicker::make('attachments')->label(__('page::page.fields.attachments'))->multiple()
                            ->helperText(__('page::page.help.attachments')),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    /** @return array<int, string> id => "Parent / Child" */
    public static function parentOptions(?string $ownerKey, ?Page $record): array
    {
        if (blank($ownerKey)) {
            return [];
        }

        $exclude = $record?->exists ? $record->descendantIds() : [];

        return Page::query()->ownedBy($ownerKey)->with('parent.parent')->whereKeyNot($exclude)->get()
            ->mapWithKeys(fn (Page $page): array => [$page->id => collect([$page->parent?->parent?->title, $page->parent?->title, $page->title])->filter()->implode(' / ')])
            ->sort()->all();
    }

    protected static function addressPrefix(Get $get, ?Page $record): string
    {
        $page = (new Page)->setOwnerKey($get('owner_key') ?: 'site:0');
        $page->parent_id = $get('parent_id');
        $parentPath = $page->parent_id && ($parent = Page::query()->find($page->parent_id)) ? Str::after($parent->url(), $parent->ownerUrlPrefix()) : '';

        return $page->ownerUrlPrefix().$parentPath.'/';
    }
}
