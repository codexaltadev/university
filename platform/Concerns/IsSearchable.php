<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Platform\Search\IndexSearchDocument;

/**
 * The record is in the website search (C-17, FR-SRCH-01) once its module registers
 * it: $r->searchable(Faq::class, label: 'faq::faq.title', weight: 6). Saving,
 * deleting or restoring queues an index update (FR-SRCH-09). The record needs
 * url($locale). Override toSearchDocument() for other fields.
 */
trait IsSearchable
{
    public static function bootIsSearchable(): void
    {
        foreach (['saved', 'deleted', 'restored', 'forceDeleted'] as $event) {
            if (method_exists(static::class, $event)) {
                static::$event(fn (Model $model) => IndexSearchDocument::dispatch($model->getMorphClass(), $model->getKey()));
            }
        }
    }

    /**
     * What the index keeps for one language; null = not in this language.
     *
     * @return array{title: ?string, body: ?string, url?: ?string}|null
     */
    public function toSearchDocument(string $locale): ?array
    {
        $text = function (string $attribute) use ($locale): ?string {
            if (method_exists($this, 'getTranslation') && in_array($attribute, $this->translatable ?? [], true)) {
                $value = $this->getTranslation($attribute, $locale, false);

                return is_string($value) ? $value : null;
            }

            $value = $this->getAttribute($attribute);

            return is_string($value) ? $value : null;
        };

        $title = $text(static::$titleAttribute ?? 'title');

        if (blank($title)) {
            return null;   // nothing written in this language
        }

        $body = collect($this->searchBodyAttributes())->map($text)->filter()->implode(' ');

        return ['title' => $title, 'body' => $body];
    }

    /** @return list<string> fields searched besides the title */
    protected function searchBodyAttributes(): array
    {
        return ['excerpt', 'summary', 'description', 'answer', 'body'];
    }

    /** Plain text for the index: tags and repeated spaces removed. */
    public static function plainText(mixed $value): string
    {
        if (is_array($value)) {
            $value = collect($value)->flatten()->filter(fn ($v): bool => is_string($v))->implode(' ');
        }

        // Block-level tags become spaces so words never run together; inline tags just go
        $value = preg_replace('#<(br|/?p|/?div|/?li|/?h[1-6]|/?td|/?th|/?tr|/?blockquote)\b[^>]*>#i', ' ', (string) $value);

        return Str::of(html_entity_decode(strip_tags((string) $value)))->squish()->toString();
    }
}
