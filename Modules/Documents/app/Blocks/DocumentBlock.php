<?php

namespace Modules\Documents\Blocks;

use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\Documents\Models\Document;
use Platform\Pages\PageBlock;

/**
 * "Documents" on any page: picked documents as a list, or one PDF as its flipbook (FR-DOC-03) —
 * e.g. the prospectus inside the Admission page. Only current, visible, public documents.
 */
final class DocumentBlock extends PageBlock
{
    public static function name(): string
    {
        return 'documents';
    }

    public static function label(): string
    {
        return __('documents::documents.block.label');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedDocumentText;
    }

    public static function view(): string
    {
        return 'documents::blocks.documents';
    }

    public static function schema(): array
    {
        $label = fn (Document $d): string => $d->title.($d->version_label ? " ({$d->version_label})" : '');

        return [
            Select::make('documents')->label(__('documents::documents.block.documents'))->multiple()->searchable()->required()
                ->getSearchResultsUsing(fn (string $search): array => Document::query()->current()->where('title', 'like', '%'.$search.'%')->limit(30)->get()->mapWithKeys(fn (Document $d): array => [$d->id => $label($d)])->all())
                ->getOptionLabelsUsing(fn (array $values): array => Document::query()->whereKey($values)->get()->mapWithKeys(fn (Document $d): array => [$d->id => $label($d)])->all()),
            Radio::make('display')->label(__('documents::documents.block.display'))->inline()->default('list')
                ->options(['list' => __('documents::documents.layouts.list'), 'flipbook' => __('documents::documents.block.flipbook')])
                ->helperText(__('documents::documents.block.flipbook_help')),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Document>
     */
    public static function documents(array $data): Collection
    {
        $picked = array_map('intval', (array) ($data['documents'] ?? []));
        $found = Document::query()->frontendVisible()->current()->forVisitor(false)->whereKey($picked)->get()->keyBy('id');

        return collect($picked)->map(fn (int $id) => $found->get($id))->filter()->values();
    }
}
