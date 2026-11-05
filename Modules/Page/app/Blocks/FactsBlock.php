<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Platform\Filament\Components\HtmlEditor;
use Platform\Pages\PageBlock;
use Platform\Theme\Themes;

/**
 * Key facts (At a Glance, UI/UX Part B §5.8 v0.6): numbers on top, then chapters (University, Academics …)
 * of facts — each a label and its value as written (HTML: lists, links, tables of the old page, cleaned
 * as the HTML block's) — and a note. The text is the university's approved one; how a fact shows (big
 * number, chips, bars, cards, a timeline by year) is found from its value unless the admin picks it
 * (Support\FactsPage). S15A makes it from an old article that is one two-column table.
 */
final class FactsBlock extends PageBlock
{
    /** How a fact can show; "auto" lets FactsPage find it from the value */
    public const DISPLAYS = ['auto', 'text', 'stat', 'chips', 'cards', 'bars', 'timeline'];

    public static function name(): string
    {
        return 'facts';
    }

    public static function label(): string
    {
        return __('page::page.blocks.facts');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedTableCells;
    }

    public static function schema(): array
    {
        return [
            Repeater::make('highlights')->label(__('page::page.fields.highlights'))
                ->schema([
                    TextInput::make('value')->label(__('page::page.fields.value'))->required()->maxLength(30),
                    TextInput::make('label')->label(__('page::page.fields.label'))->required()->maxLength(80),
                ])
                ->columns(2)->maxItems(6)->reorderable()->defaultItems(0)
                ->helperText(__('page::page.help.highlights')),
            Repeater::make('chapters')->label(__('page::page.fields.chapters'))
                ->schema([
                    TextInput::make('title')->label(__('page::page.fields.chapter'))->maxLength(60),
                    Repeater::make('rows')->label(__('page::page.fields.rows'))
                        ->schema([
                            TextInput::make('label')->label(__('page::page.fields.label'))->required()->maxLength(150),
                            HtmlEditor::make('value')->label(__('page::page.fields.body'))->required()->height(220)
                                ->contentCss(fn (): array => array_map(theme_asset(...), app(Themes::class)->manifest()->editorCss())),
                            Select::make('display')->label(__('page::page.fields.display'))->default('auto')->selectablePlaceholder(false)
                                ->options(collect(self::DISPLAYS)->mapWithKeys(fn (string $d): array => [$d => __('page::page.displays.'.$d)])->all())
                                ->helperText(__('page::page.help.display')),
                            Toggle::make('featured')->label(__('page::page.fields.featured'))->inline(false),
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                        ->collapsible()->collapsed()->cloneable()->reorderable()->minItems(1),
                ])
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->collapsible()->reorderable()->minItems(1),
            Toggle::make('show_nav')->label(__('page::page.fields.show_nav'))->default(true)
                ->helperText(__('page::page.help.show_nav')),
            HtmlEditor::make('note')->label(__('page::page.fields.note'))->height(120),
        ];
    }

    /** Media Library files inserted in the values and the note (data-media-id) */
    public static function mediaIds(array $data): array
    {
        $rows = collect((array) ($data['chapters'] ?? []))->flatMap(fn ($chapter): array => (array) ($chapter['rows'] ?? []))
            ->merge((array) ($data['rows'] ?? []));

        return HtmlBlock::mediaIds(['html' => $rows->pluck('value')->push($data['note'] ?? '')->implode(' ')]);
    }
}
