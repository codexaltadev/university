<?php

namespace Modules\Testimonials\Support;

use Illuminate\Support\Str;
use Modules\Testimonials\Models\Testimonial;

/**
 * What the testimonials page shows, ready for a theme (`$page` in testimonials::index), as DIU does:
 * a card per testimonial — the start of the words, the person — that opens to all of it; the types
 * to choose from. Themes hold no PHP logic (T-1).
 */
class TestimonialsPage
{
    public const EXCERPT = 200;

    public function __construct(protected array $data) {}

    public function listingUrl(): string
    {
        return url(($this->data['base'] ?? '').'/'.Testimonial::PATH);
    }

    /** @return list<array{key: ?string, label: string, url: string, current: bool}> "All" and the types that have testimonials here */
    public function types(): array
    {
        $types = $this->data['types'] ?? [];
        if (count($types) < 2) {
            return [];
        }
        $type = $this->data['type'] ?? null;

        return [
            ['key' => null, 'label' => __('testimonials::testimonials.all'), 'url' => $this->listingUrl(), 'current' => $type === null],
            ...array_map(fn (string $t): array => ['key' => $t, 'label' => __("testimonials::testimonials.types_plural.{$t}"), 'url' => $this->listingUrl().'?type='.$t, 'current' => $type === $t], $types),
        ];
    }

    /**
     * @return list<array{id: string, excerpt: string, quote: string, name: ?string, title: ?string, profile: ?string, photo: ?string, type: string, rating: ?int, video: ?string}>
     */
    public function cards(): array
    {
        return collect($this->data['items'] ?? [])->map(function (Testimonial $t): array {
            $person = $t->person();
            $quote = trim(html_entity_decode(strip_tags((string) $t->quote)));

            return [
                'id' => 'testimonial-'.$t->getKey(),
                'excerpt' => Str::limit($quote, self::EXCERPT),
                'quote' => $quote,
                'name' => $person?->name(),
                'title' => $person ? ($person->assignment->role_label ?: $person->designation()) : null,
                'profile' => $person?->profileUrl(),
                'photo' => $t->photoUrl(),
                'type' => __('testimonials::testimonials.types.'.$t->testimonial_type),
                'rating' => $t->rating ? (int) $t->rating : null,
                'video' => $t->videoEmbed(),
            ];
        })->all();
    }
}
