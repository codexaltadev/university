<?php

namespace Modules\Gallery\Sections;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Gallery\Models\Album;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/**
 * Homepage section "Photo Gallery" (FR-GAL-07): the place's albums in the builder's source mode
 * (latest, highlighted first, highlighted, picked), shown as album covers or as their pictures.
 */
class PhotoGallerySection extends Section
{
    public static function key(): string
    {
        return 'gallery.photos';
    }

    public static function label(): string
    {
        return __('gallery::gallery.section.photos');
    }

    public static function view(): string
    {
        return 'gallery::sections.photos';
    }

    public static function model(): ?string
    {
        return Album::class;
    }

    public static function settingsSchema(): array
    {
        return [
            Radio::make('display')->label(__('gallery::gallery.section.display'))->inline()->default('albums')
                ->options(['albums' => __('gallery::gallery.section.albums'), 'photos' => __('gallery::gallery.section.pictures')]),
            TextInput::make('photos')->label(__('gallery::gallery.section.photo_count'))->integer()->minValue(1)->maxValue(48)->default(12),
        ];
    }

    /** @return Collection<int, Album> */
    public function items(SectionContext $ctx): Collection
    {
        return $ctx->applySourceMode(Album::class, defaultCount: 4);
    }
}
