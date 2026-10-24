<?php

/*
| Phase 3.17 — M-22 Gallery (DB-05 §7 v1.6, SRS Content FR-GAL-01..07, BRL-GAL-01): an album's
| pictures are Media Library items it only uses — no copies, one picture in many albums, its own
| caption per album, dragged order, "Add a folder"; link videos with episode and speaker;
| /gallery and /{entity}/gallery, the album page, the two homepage sections, the event page.
*/

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Events\Models\Event;
use Modules\Faculty\Models\Faculty;
use Modules\Gallery\Filament\Components\AlbumItems;
use Modules\Gallery\Filament\Resources\Albums\Pages\CreateAlbum;
use Modules\Gallery\Filament\Resources\Albums\Pages\EditAlbum;
use Modules\Gallery\Models\Album;
use Modules\Gallery\Models\Video;
use Modules\Gallery\Sections\PhotoGallerySection;
use Modules\Gallery\Sections\VideoGallerySection;
use Modules\Gallery\Support\Speakers;
use Modules\Gallery\Support\VideoLink;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Homepage\SectionContext;
use Platform\Media\MediaFolder;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
use Platform\Media\MediaUsage;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Gallery');
    app(ModulePermissions::class)->syncAttachable('gallery', [Album::class]);
    app(RoleCatalog::class)->sync();
    $this->engine = app(RelationEngine::class);
    $this->library = app(MediaLibrary::class);
    $this->photo = fn (string $name, array $attributes = []): MediaItem => $this->library->store(UploadedFile::fake()->image($name, 40, 30), $attributes);
    $this->album = function (string $title, array $photos = [], bool $main = true, array $places = [], array $attributes = []): Album {
        $album = Album::factory()->create(['title' => ['en' => $title], ...$attributes]);
        AlbumItems::save($album, array_map(fn ($p) => is_array($p) ? $p : ['media' => $p->id], $photos));
        $this->engine->sync($album, $places, mainSite: $main, user: null);

        return $album;
    };
});

it('uses Media Library pictures without copying them: one picture in two albums, own caption, order (BRL-GAL-01)', function () {
    $stage = tap(($this->photo)('stage.jpg'))->update(['caption' => ['en' => 'The main stage']]);   // the caption kept in the library
    $crowd = ($this->photo)('crowd.jpg');
    $files = MediaItem::query()->count();

    $this->actingAs(superAdmin());
    Livewire::test(CreateAlbum::class)
        ->fillForm([
            'title' => ['en' => '9th Convocation'], 'show_in' => [], 'main_site' => true,
            'items' => [
                ['media' => $crowd->id, 'caption' => ['en' => 'Graduates in the hall']],
                ['media' => $stage->id, 'caption' => []],
            ],
        ])
        ->call('create')->assertHasNoFormErrors();
    $convocation = Album::query()->sole();
    $other = ($this->album)('Campus life', [$stage]);

    expect(MediaItem::query()->count())->toBe($files)                                                   // nothing copied
        ->and($convocation->entries()->map(fn (array $e): array => [$e['item']->id, $e['caption']])->all())
        ->toBe([[$crowd->id, 'Graduates in the hall'], [$stage->id, 'The main stage']])                // own caption, else the item's
        ->and($stage->fresh()->usage_count)->toBe(2)
        ->and(MediaUsage::query()->where('media_item_id', $stage->id)->pluck('usable_id')->sort()->values()->all())->toBe([$convocation->id, $other->id]);

    $this->get($convocation->url())->assertOk()->assertSeeInOrder(['Graduates in the hall', 'The main stage']);

    Livewire::test(EditAlbum::class, ['record' => $convocation->id])
        ->assertSet('data.items', fn (array $rows): bool => array_column(array_values($rows), 'media') === [$crowd->id, $stage->id]);
});

it('adds every picture of a Media Library folder at once, and only pictures and videos', function () {
    $folder = MediaFolder::query()->create(['name' => 'Convocation 2026']);
    $a = ($this->photo)('a.jpg', ['folder_id' => $folder->id]);
    $b = ($this->photo)('b.jpg', ['folder_id' => $folder->id]);
    $pdf = $this->library->store(UploadedFile::fake()->createWithContent('rules.pdf', '%PDF-1.4 test'), ['folder_id' => $folder->id]);
    $album = ($this->album)('Convocation', [$b]);

    $this->actingAs(superAdmin());
    Livewire::test(EditAlbum::class, ['record' => $album->id])
        ->callAction(TestAction::make('folder')->schemaComponent('items'), data: ['folder' => $folder->id])
        ->call('save')->assertHasNoFormErrors();

    expect($album->fresh()->mediaIdsIn(Album::ITEMS))->toBe([$b->id, $a->id]);                         // b kept once, a added, the PDF left out
    AlbumItems::save($album, [['media' => $pdf->id]]);
    expect($album->fresh()->mediaIdsIn(Album::ITEMS))->toBe([]);
});

it('lists the main site\'s albums newest first, by category and kind, and an entity\'s on its microsite (FR-GAL-03/05)', function () {
    Vocabularies::ensureTerms(Album::CATEGORY, [['name' => ['en' => 'Convocation'], 'slug' => 'convocation']]);
    $term = (int) array_search('Convocation', app(Vocabularies::class)->options(Album::CATEGORY), true);
    $old = ($this->album)('MoU 2019', [($this->photo)('m.jpg')], attributes: ['occurred_on' => '2019-05-01']);
    $new = ($this->album)('6th Convocation', [($this->photo)('c.jpg')], attributes: ['occurred_on' => '2025-01-10']);
    $new->syncTerms(Album::CATEGORY, [$term]);
    $docs = ($this->album)('Documentaries', attributes: ['series_type' => 'documentary']);
    Video::query()->create(['album_id' => $docs->id, 'url' => 'https://www.youtube.com/watch?v=rEd8E_PJtYQ', 'title' => ['en' => 'CSE Documentary']]);
    ($this->album)('Hidden', attributes: ['is_active' => false]);
    $cse = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id, 'short_name' => 'CSE']);
    ($this->album)('CSE Study Tour', [($this->photo)('t.jpg')], main: false, places: ['department.department:'.$cse->id]);

    // DIU's gallery: "Browse by Category" — the category with its albums, the rest after — and the album cards
    $this->get('/gallery')->assertOk()->assertSeeInOrder(['Browse by Category (02)', 'Convocation', '6th Convocation', 'Other albums', 'MoU 2019', 'Albums', '6th Convocation', 'MoU 2019'])
        ->assertDontSee('Hidden')->assertDontSee('CSE Study Tour');
    // A category: its albums' pictures (Media Library items), 20 a page
    $this->get('/gallery?category=convocation')->assertOk()->assertSeeInOrder(['Convocation', 'Showing 1 - 1 of 1 photos', $new->entries('image')->first()['item']->url('medium') ?? $new->entries('image')->first()['item']->url()], false);
    // The module's own listing (any theme without its version) keeps its filters
    $listing = fn (array $albums) => moduleView('Gallery', 'index', ['albums' => new LengthAwarePaginator(collect($albums), count($albums), 12), 'categories' => collect(), 'category' => null, 'kind' => null, 'layout' => 'cards', 'base' => '']);
    expect($listing([$new, $old]))->toContain('6th Convocation')->toContain('MoU 2019');
    $this->get('/gallery?kind=videos')->assertOk()->assertSeeInOrder(['Albums', 'Documentaries'])->assertDontSee('1 photos · 1 May 2019', false);

    $base = rtrim($cse->url(), '/');
    $this->get($base.'/gallery')->assertOk()->assertSee('CSE Study Tour')->assertDontSee('MoU 2019');
    $this->get('/gallery/'.Album::query()->get()->first(fn (Album $a): bool => $a->title === 'CSE Study Tour')->slug())->assertNotFound();
    $this->get($docs->url())->assertOk()->assertSee('CSE Documentary')->assertSee('youtube-nocookie.com/embed/rEd8E_PJtYQ', false);
});

it('reads YouTube, Vimeo and Facebook links and refuses others (FR-GAL-02)', function () {
    expect(VideoLink::parse('https://youtu.be/6UWCe_nywIU?t=163'))->toBe(['provider' => 'youtube', 'id' => '6UWCe_nywIU'])
        ->and(VideoLink::parse('https://www.youtube.com/embed/fTLlAUAxg48'))->toBe(['provider' => 'youtube', 'id' => 'fTLlAUAxg48'])
        ->and(VideoLink::parse('https://vimeo.com/76979871'))->toBe(['provider' => 'vimeo', 'id' => '76979871'])
        ->and(VideoLink::parse('https://www.facebook.com/diu/videos/1234567890/'))->toBe(['provider' => 'facebook', 'id' => '1234567890'])
        ->and(VideoLink::parse('https://example.com/movie.mp4'))->toBeNull();

    $album = ($this->album)('Videos');
    expect(fn () => Video::query()->create(['album_id' => $album->id, 'url' => 'https://example.com/x']))->toThrow(ValidationException::class);
});

it('shows a lecture series with episodes and speakers (FR-GAL-04)', function () {
    $series = ($this->album)('Lecture Series', attributes: ['series_type' => 'lecture_series']);
    $talk = Video::query()->create(['album_id' => $series->id, 'url' => 'https://youtu.be/6UWCe_nywIU', 'title' => ['en' => 'Leadership'], 'episode_no' => 12, 'duration_seconds' => 3930]);
    app(Speakers::class)->set($talk, ManualPerson::query()->create(['name' => ['en' => 'Mahbubur Rahman'], 'designation' => ['en' => 'President, ICCB'], 'is_active' => true]));

    $this->get($series->url())->assertOk()->assertSee('Lecture series')
        ->assertSeeInOrder(['Episode 12', 'Leadership', 'Mahbubur Rahman', 'President, ICCB', '1:05:30']);
});

it('fills "Photo Gallery" and "Video Gallery" from the place\'s albums (FR-GAL-07)', function () {
    $older = ($this->album)('Older', [($this->photo)('o.jpg')], attributes: ['occurred_on' => '2024-01-01']);
    $newer = ($this->album)('Newer', [($this->photo)('n.jpg')], attributes: ['occurred_on' => '2026-01-01']);
    Video::query()->create(['album_id' => $older->id, 'url' => 'https://youtu.be/aaaaaaaaaaa', 'title' => ['en' => 'Old talk']]);
    Video::query()->create(['album_id' => $newer->id, 'url' => 'https://youtu.be/bbbbbbbbbbb', 'title' => ['en' => 'New talk']]);
    Video::query()->create(['album_id' => $newer->id, 'url' => 'https://youtu.be/ccccccccccc', 'title' => ['en' => 'Off'], 'is_active' => false]);

    $albums = (new PhotoGallerySection)->items(new SectionContext('site:0', ['source_mode' => 'latest', 'count' => 4], 'en'));
    expect($albums->pluck('id')->sort()->values()->all())->toBe(collect([$older->id, $newer->id])->sort()->values()->all());

    $videos = (new VideoGallerySection)->items(new SectionContext('site:0', ['count' => 5], 'en'));
    expect($videos->map(fn (Video $v): string => $v->title)->all())->toBe(['New talk', 'Old talk']);
});

it('shows an event\'s albums on the event page (FR-GAL-06)', function () {
    $event = Event::factory()->create(['title' => ['en' => 'Convocation 2026'], 'starts_at' => now()->subDay()]);
    $this->engine->sync($event, [], mainSite: true, user: null);
    ($this->album)('Convocation photos', [($this->photo)('x.jpg')], attributes: ['event_id' => $event->id]);

    $this->get($event->url())->assertOk()->assertSee('Photo albums')->assertSee('Convocation photos');
});
