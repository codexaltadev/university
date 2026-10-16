<?php

namespace Modules\Achievements\Sections;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Modules\Achievements\Filament\Resources\Achievements\AchievementResource;
use Modules\Achievements\Models\Achievement;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/**
 * Homepage section "Recent Achievements" (FR-ACH-03): the place's achievements in the builder's
 * source mode — "latest" by the date achieved — optionally of one level; cards or a slider.
 */
class RecentAchievementsSection extends Section
{
    public static function key(): string
    {
        return 'achievements.recent';
    }

    public static function label(): string
    {
        return __('achievements::achievements.section.recent');
    }

    public static function view(): string
    {
        return 'achievements::sections.recent';
    }

    public static function model(): ?string
    {
        return Achievement::class;
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('level')->label(__('achievements::achievements.fields.level'))->options(AchievementResource::levelOptions())
                ->placeholder(__('achievements::achievements.all')),
            Radio::make('display')->label(__('achievements::achievements.section.display'))->inline()->default('cards')
                ->options(['cards' => __('achievements::achievements.layouts.cards'), 'slider' => __('achievements::achievements.section.slider')]),
        ];
    }

    /** @return Collection<int, Achievement> */
    public function items(SectionContext $ctx): Collection
    {
        $mode = $ctx->setting('source_mode', 'highlighted_first');
        if ($mode === 'manual') {
            return $ctx->applySourceMode(Achievement::class);
        }

        $count = max(1, (int) $ctx->setting('count', 6));
        $level = in_array($ctx->setting('level'), Achievement::LEVELS, true) ? $ctx->setting('level') : null;
        $content = fn () => app(RelationEngine::class)->for($ctx->ownerKey)->content(Achievement::class)
            ->includeDescendants((bool) $ctx->setting('include_descendants', false));

        $highlighted = $mode === 'latest' ? (new Achievement)->newCollection()
            : $content()->highlighted(100)->filter(fn (Achievement $a): bool => $level === null || $a->level === $level)->take($count)->values();
        if ($mode === 'highlighted' || $highlighted->count() >= $count) {
            return (new Achievement)->newCollection($highlighted->all());
        }

        // Then by the date achieved, newest first — the achievement's own date, not when it was entered
        $rest = $content()->query()->ofLevel($level)->newest()
            ->whereKeyNot($highlighted->modelKeys())->limit($count - $highlighted->count())->get();

        return (new Achievement)->newCollection($highlighted->concat($rest)->values()->all());
    }
}
