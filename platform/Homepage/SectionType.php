<?php

namespace Platform\Homepage;

use Closure;

/**
 * One kind of homepage section in the builder's catalogue (C-10): either a Section class
 * (core or a module's) or a section the active theme declares in theme.json (Theme Guide
 * §8.4). The builder and the website only talk to this.
 */
final class SectionType
{
    /**
     * @param  class-string|null  $model  content model: the builder offers source modes
     * @param  Closure(): array  $schema  the section's own settings fields
     * @param  Closure(SectionContext): mixed  $items  data for the view
     * @param  Closure(SectionContext): ?array  $content  a theme section's field values for the language
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $view,
        public readonly ?string $model,
        private readonly Closure $schema,
        private readonly Closure $items,
        private readonly ?Closure $content = null,
    ) {}

    /** @param class-string<Section> $class */
    public static function fromClass(string $class): self
    {
        return new self(
            key: $class::key(),
            label: $class::label(),
            view: $class::view(),
            model: $class::model(),
            schema: fn (): array => $class::settingsSchema(),
            items: fn (SectionContext $ctx): mixed => app($class)->items($ctx),
        );
    }

    public function isTheme(): bool
    {
        return str_starts_with($this->key, ThemeSections::PREFIX);
    }

    public function schema(): array
    {
        return ($this->schema)();
    }

    public function items(SectionContext $ctx): mixed
    {
        return ($this->items)($ctx);
    }

    /** @return array<string, mixed>|null */
    public function content(SectionContext $ctx): ?array
    {
        return $this->content ? ($this->content)($ctx) : null;
    }
}
