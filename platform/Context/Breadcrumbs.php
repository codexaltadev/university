<?php

namespace Platform\Context;

use Illuminate\Database\Eloquent\Model;

/**
 * The microsite trail (UI/UX Part B §3 v0.2): Home › FSIT › CSE › BSc in CSE, each to its
 * microsite — ancestors by short name, the current entity by its name.
 */
class Breadcrumbs
{
    /** @return list<array{label: string, url: ?string}> empty on the main site */
    public static function for(SiteContext $context, ?string $locale = null): array
    {
        if (! $context->isMicrosite()) {
            return [];
        }

        $resolver = app(MicrositeResolver::class);
        $prefix = static::localePrefix($locale);
        $nodes = array_reverse([$context->entity, ...$resolver->ancestors($context->entity)]);

        $trail = [['label' => __('platform::relations.home'), 'url' => $prefix.'/']];
        foreach ($nodes as $i => $node) {
            $url = $resolver->urlFor($node, $locale);
            $trail[] = ['label' => static::label($node, last: $i === count($nodes) - 1), 'url' => $url ? $prefix.$url : null];
        }

        return $trail;
    }

    protected static function label(Model $entity, bool $last): string
    {
        $name = (string) ($entity->name ?? $entity->title ?? '');

        return ! $last && filled($entity->short_name ?? null) ? (string) $entity->short_name : $name;
    }

    /** "" for the default language, else "/bn" … */
    public static function localePrefix(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $default = config('platform.locales')[0] ?? 'en';

        return $locale === $default ? '' : "/{$locale}";
    }
}
