<?php

namespace Platform\Website;

use Illuminate\Support\HtmlString;
use Platform\Context\SiteContext;
use Platform\Registry\Registry;
use Throwable;

/**
 * Footer blocks of active modules (FooterBlock). A block that fails is left out and reported,
 * so the footer never breaks the page.
 */
class FooterBlocks
{
    public function __construct(protected Registry $registry) {}

    /** @return array<string, class-string<FooterBlock>> key => class */
    public function all(): array
    {
        return collect($this->registry->all('footerBlock'))
            ->pluck('class')->filter(fn (string $class): bool => class_exists($class))
            ->mapWithKeys(fn (string $class): array => [$class::key() => $class])->all();
    }

    /** @param array<string, mixed> $block */
    public function render(array $block, ?SiteContext $context = null): ?HtmlString
    {
        $class = $this->all()[$block['type'] ?? ''] ?? null;
        if ($class === null) {
            return null;
        }

        try {
            $context ??= SiteContext::site();
            $html = view($class::view(), ['block' => $block, 'context' => $context, ...app($class)->data($block, $context)])->render();

            return trim($html) === '' ? null : new HtmlString($html);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
