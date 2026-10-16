<?php

namespace Modules\Gallery\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Modules\Gallery\Filament\Resources\Albums\AlbumResource;
use Modules\Gallery\Models\Video;
use Modules\Gallery\Support\GalleryWebsite;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/**
 * Homepage section "Video Gallery" (FR-GAL-07): the videos of the place's albums, newest album
 * first and in each album's order; optionally one series only (documentaries, lectures).
 */
class VideoGallerySection extends Section
{
    public static function key(): string
    {
        return 'gallery.videos';
    }

    public static function label(): string
    {
        return __('gallery::gallery.section.videos');
    }

    public static function view(): string
    {
        return 'gallery::sections.videos';
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('series')->label(__('gallery::gallery.fields.series_type'))->options(AlbumResource::seriesOptions())
                ->placeholder(__('gallery::gallery.all')),
            TextInput::make('count')->label(__('gallery::gallery.section.count'))->integer()->minValue(1)->maxValue(24)->default(6),
        ];
    }

    /** @return Collection<int, Video> */
    public function items(SectionContext $ctx): Collection
    {
        $albums = GalleryWebsite::shown($ctx->ownerKey, (bool) $ctx->setting('include_descendants', false))
            ->when($ctx->setting('series'), fn ($query, $series) => $query->where('series_type', $series))
            ->limit(50)->pluck('gallery_albums.id')->all();
        if ($albums === []) {
            return collect();
        }
        $rank = array_flip($albums);

        return Video::query()->shown()->whereIn('album_id', $albums)->get()
            ->sortBy([fn (Video $a, Video $b) => $rank[$a->album_id] <=> $rank[$b->album_id], fn (Video $a, Video $b) => [$a->sort_order, $a->id] <=> [$b->sort_order, $b->id]])
            ->take(max(1, (int) ($ctx->setting('count') ?: 6)))->values();
    }
}
