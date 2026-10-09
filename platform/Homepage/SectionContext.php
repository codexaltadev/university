<?php

namespace Platform\Homepage;

use Illuminate\Support\Collection;
use Platform\Context\MicrositeOwners;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;

/**
 * What a section is rendered for (FR-HOME-05/11): the homepage's owner (main site or
 * an entity), the section's settings and the language.
 */
final class SectionContext
{
    public const MODES = ['latest', 'highlighted', 'highlighted_first', 'manual'];

    /** @param array<string, mixed> $settings */
    public function __construct(
        public readonly string $ownerKey,
        public readonly array $settings,
        public readonly string $locale,
    ) {}

    public function site(): SiteContext
    {
        return MicrositeOwners::context($this->ownerKey);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Content of this homepage's context in the section's source mode (FR-HOME-11,
     * SAD Part B §3.4): main homepage = content published on the main site; a microsite
     * = content shown in the entity (and below it when the section says so);
     * highlights of this context first by default.
     *
     * @param  class-string  $model
     */
    public function applySourceMode(string $model, int $defaultCount = 6): Collection
    {
        return app(RelationEngine::class)->for($this->ownerKey)
            ->content($model)
            ->includeDescendants((bool) $this->setting('include_descendants', false))
            ->mode(
                in_array($this->setting('source_mode'), self::MODES, true) ? $this->setting('source_mode') : 'highlighted_first',
                max(1, (int) $this->setting('count', $defaultCount)),
                array_map('intval', (array) $this->setting('manual_ids', [])),
            );
    }
}
