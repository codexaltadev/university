<?php

namespace Modules\Documents\Sections;

use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Modules\Documents\Filament\Resources\Documents\DocumentResource;
use Modules\Documents\Models\Document;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;
use Platform\Relations\RelationEngine;

/**
 * Homepage section "Downloads" (FR-DOC-08): the place's current, public documents — highlighted
 * first, then the newest (the builder's source mode); only some types when chosen.
 */
class DownloadsSection extends Section
{
    public static function key(): string
    {
        return 'documents.downloads';
    }

    public static function label(): string
    {
        return __('documents::documents.section.downloads');
    }

    public static function view(): string
    {
        return 'documents::sections.downloads';
    }

    public static function model(): ?string
    {
        return Document::class;
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('types')->label(__('documents::documents.fields.type'))->options(DocumentResource::typeOptions())->multiple()
                ->helperText(__('documents::documents.section.types_help')),
        ];
    }

    /** @return Collection<int, Document> */
    public function items(SectionContext $ctx): Collection
    {
        $mode = $ctx->setting('source_mode', 'highlighted_first');
        if ($mode === 'manual') {
            return $ctx->applySourceMode(Document::class)->filter(fn (Document $d): bool => ! $d->is_private)->values();
        }

        return self::pick($ctx, array_values(array_intersect(Document::TYPES, (array) $ctx->setting('types'))), $mode, max(1, (int) $ctx->setting('count', 6)));
    }

    /**
     * Highlighted first (unless "latest"), then the newest; current and public only.
     *
     * @param  list<string>  $types
     * @return Collection<int, Document>
     */
    public static function pick(SectionContext $ctx, array $types, string $mode, int $count): Collection
    {
        $content = fn () => app(RelationEngine::class)->for($ctx->ownerKey)->content(Document::class)
            ->includeDescendants((bool) $ctx->setting('include_descendants', false));
        $fits = fn (Document $d): bool => $d->superseded_by_id === null && ! $d->is_private && ($types === [] || in_array($d->document_type, $types, true));

        $highlighted = $mode === 'latest' ? (new Document)->newCollection() : $content()->highlighted(100)->filter($fits)->take($count)->values();
        if ($mode === 'highlighted' || $highlighted->count() >= $count) {
            return (new Document)->newCollection($highlighted->all());
        }

        $rest = $content()->query()->current()->forVisitor(false)->newest()
            ->when($types !== [], fn ($query) => $query->whereIn('document_type', $types))
            ->whereKeyNot($highlighted->modelKeys())->limit($count - $highlighted->count())->get();

        return (new Document)->newCollection($highlighted->concat($rest)->values()->all());
    }
}
