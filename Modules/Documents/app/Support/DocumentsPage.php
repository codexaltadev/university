<?php

namespace Modules\Documents\Support;

use Modules\Documents\Models\Document;

/**
 * What the downloads page shows, ready for a theme (`$page` in documents::index and documents::show),
 * as DIU's "Digital Brochures" / "Program Prospectus" pages do: a card per document — thumbnail,
 * its kind, title, "View Flipbook" (or Read) and a download — with the kinds and categories to
 * choose from. Themes hold no PHP logic (T-1).
 */
class DocumentsPage
{
    public function __construct(protected array $data) {}

    public function listingUrl(): string
    {
        return url(($this->data['base'] ?? '').'/'.Document::PATH);
    }

    /** The listing's address with some filters changed (null = removed); the page starts over */
    public function url(array $change = []): string
    {
        $query = array_filter(array_merge([
            'type' => $this->data['type'] ?? null,
            'category' => ($this->data['category'] ?? null)?->slug(),
            'year' => $this->data['year'] ?? null,
            'q' => ($this->data['q'] ?? '') ?: null,
        ], $change), fn ($v): bool => $v !== null && $v !== '');

        return $this->listingUrl().($query ? '?'.http_build_query($query) : '');
    }

    /** @return list<array{label: string, url: string, current: bool}> "All" and the kinds present */
    public function types(): array
    {
        $types = $this->data['types'] ?? [];
        if (count($types) < 2) {
            return [];
        }
        $type = $this->data['type'] ?? null;

        return [
            ['label' => __('documents::documents.all'), 'url' => $this->url(['type' => null]), 'current' => $type === null],
            ...array_map(fn (string $t): array => ['label' => __("documents::documents.types.{$t}"), 'url' => $this->url(['type' => $t]), 'current' => $type === $t], $types),
        ];
    }

    /** @return list<array{label: string, url: string, current: bool}> */
    public function categories(): array
    {
        $category = $this->data['category'] ?? null;

        return collect($this->data['categories'] ?? [])->map(fn ($t): array => ['label' => (string) $t->name, 'url' => $this->url(['category' => $t->slug()]), 'current' => (bool) $category?->is($t)])->all();
    }

    /** @return list<array{label: string, value: string, url: ?string}> a document's details that are filled */
    public function facts(): array
    {
        $d = $this->data['document'] ?? null;
        if (! $d instanceof Document) {
            return [];
        }
        $program = $d->program();

        return array_values(array_filter([
            $d->version_label ? ['label' => __('documents::documents.fields.version_label'), 'value' => (string) $d->version_label, 'url' => null] : null,
            $d->approved_on ? ['label' => __('documents::documents.fields.approved_on'), 'value' => $d->approved_on->translatedFormat('j F Y'), 'url' => null] : null,
            $d->effective_on ? ['label' => __('documents::documents.fields.effective_on'), 'value' => $d->effective_on->translatedFormat('j F Y'), 'url' => null] : null,
            $d->session_label ? ['label' => __('documents::documents.fields.session_label'), 'value' => (string) $d->session_label, 'url' => null] : null,
            $program ? ['label' => __('documents::documents.fields.program'), 'value' => (string) $program->name, 'url' => url($program->url())] : null,
            ($facts = $d->fileFacts()) ? ['label' => __('documents::documents.file'), 'value' => $facts, 'url' => null] : null,
        ]));
    }

    /**
     * @return list<array{title: string, url: string, type: string, thumb: ?string, flipbook: bool, readable: bool, download: ?string, external: ?string, meta: ?string}>
     */
    public function cards(): array
    {
        $base = $this->data['base'] ?? '';

        return collect(($this->data['documents'] ?? null)?->items() ?? [])->map(fn (Document $d): array => [
            'title' => (string) $d->title,
            'url' => url($base.'/'.Document::PATH.'/'.$d->slug()),
            'type' => __("documents::documents.types.{$d->document_type}"),
            'thumb' => $d->firstMedia('thumbnail')?->url('medium'),
            'flipbook' => $d->flipbook(),
            'readable' => $d->fileItem() !== null || filled($d->external_url),
            'download' => $d->allow_download && $d->fileItem() ? url($base.'/'.Document::PATH.'/'.$d->slug().'/file') : null,
            'external' => $d->fileItem() ? null : ($d->external_url ?: null),
            'meta' => collect([$d->session_label, $d->version_label, $d->program()?->name, $d->fileFacts()])->filter()->implode(' · ') ?: null,
        ])->all();
    }
}
