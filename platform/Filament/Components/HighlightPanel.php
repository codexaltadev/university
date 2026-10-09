<?php

namespace Platform\Filament\Components;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;

/**
 * Content form sidebar, "Highlight" (FR-CNT-H01/H02/H03): for every place the content
 * is shown in (main site and each "Show in" entity) a switch, an order and an
 * optional end date. Main site needs HighlightMain; entity users only their own
 * entities. Needs RelatedToField in the same form, which saves both.
 *
 *     HighlightPanel::make()
 */
class HighlightPanel
{
    public static function make(): Section
    {
        return Section::make(__('platform::relations.highlight'))
            ->description(__('platform::relations.highlight_help'))
            ->compact()
            ->schema(fn (Get $get, Component $component): array => static::fields(static::contexts($get, $component), $component));
    }

    /**
     * Highlight choices for the Relation Engine: context => [order, until] or false.
     *
     * @param  list<string>  $contexts
     * @return array<string, array{order: int, until: ?string}|false>
     */
    public static function collect(Get $get, array $contexts): array
    {
        if ($get('highlights') === null) {
            return [];   // no HighlightPanel in this form: leave highlights as they are
        }

        $result = [];

        foreach ($contexts as $context) {
            $state = $get('highlights.'.static::id($context)) ?? [];
            $result[$context] = ($state['on'] ?? false)
                ? ['order' => (int) ($state['order'] ?? 0), 'until' => $state['until'] ?? null]
                : false;
        }

        return $result;
    }

    /** @return list<string> main site (when on) and the chosen entities */
    protected static function contexts(Get $get, Component $component): array
    {
        $record = $component->getRecord();
        $engine = app(RelationEngine::class);
        $onSite = $engine->mayPublishMain(Auth::user(), RelatedToField::modelClass($component))
            ? (bool) $get('main_site')
            : (bool) $record?->isOnMainSite();

        return [...($onSite ? [RelationEngine::SITE_KEY] : []), ...array_values((array) $get('show_in'))];
    }

    /** @param list<string> $contexts */
    protected static function fields(array $contexts, Component $component): array
    {
        if ($contexts === []) {
            return [Text::make(__('platform::relations.highlight_none'))];
        }

        $engine = app(RelationEngine::class);
        $targets = app(Targets::class);
        $content = $component->getRecord() ?? new (RelatedToField::modelClass($component));
        $current = $component->getRecord()?->exists ? $engine->highlightsOf($component->getRecord()) : [];

        return collect($contexts)->map(function (string $context) use ($engine, $targets, $content, $current): Grid {
            $id = static::id($context);
            $allowed = $engine->mayHighlightIn(Auth::user(), $content, $context);
            $on = fn (Get $get): bool => (bool) $get("highlights.{$id}.on");

            return Grid::make(['default' => 3])->schema([
                Toggle::make("highlights.{$id}.on")->label($targets->label($context))
                    ->disabled(! $allowed)->live()->dehydrated(false)->columnSpanFull()
                    ->afterStateHydrated(fn (Toggle $c, ?Model $record) => $record?->exists ? $c->state(isset($current[$context])) : null),
                TextInput::make("highlights.{$id}.order")->label(__('platform::relations.order'))
                    ->numeric()->integer()->minValue(0)->default(0)
                    ->disabled(! $allowed)->visible($on)->dehydrated(false)
                    ->afterStateHydrated(fn (TextInput $c) => isset($current[$context]) ? $c->state($current[$context]['order']) : null),
                DatePicker::make("highlights.{$id}.until")->label(__('platform::relations.until'))
                    ->minDate(today())
                    ->disabled(! $allowed)->visible($on)->dehydrated(false)->columnSpan(2)
                    ->afterStateHydrated(fn (DatePicker $c) => isset($current[$context]) ? $c->state($current[$context]['until']) : null),
            ]);
        })->all();
    }

    /** State key for a context ("department.department:12" has dots and a colon). */
    public static function id(string $context): string
    {
        return 'c'.substr(md5($context), 0, 12);
    }
}
