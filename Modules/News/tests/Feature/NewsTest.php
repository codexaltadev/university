<?php

/*
| Phase 3.13 — M-18 News (DB-05 §3 v1.2, SRS Content v0.2 FR-NEWS-01..06): shown in entities and,
| by the Admin, on the main site (AC-NEWS-01/03); highlights as "Featured" with an end
| (AC-NEWS-04); active_at scheduling (AC-NEWS-02); /news with category, tag and archive; detail
| layouts, gallery and video; the three homepage sections; block, search, sitemap, views.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\News\Blocks\NewsBlock;
use Modules\News\Database\Seeders\NewsDatabaseSeeder;
use Modules\News\Filament\Resources\Posts\Pages\CreatePost;
use Modules\News\Models\Post;
use Modules\News\Sections\FeaturedNewsSection;
use Modules\News\Sections\LatestNewsSection;
use Modules\News\Sections\NewsByCategorySection;
use Modules\News\Settings\NewsSettings;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Search\SearchIndex;
use Platform\Seo\Sitemap;
use Platform\Support\PublishDate;
use Platform\Taxonomy\Vocabularies;
use Platform\Views\ViewCounter;

beforeEach(function () {
    Carbon::setTestNow('2026-05-10 09:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'News'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    app(ModulePermissions::class)->syncAttachable('news', [Post::class]);   // PublishMain:Post, HighlightMain:Post
    (new NewsDatabaseSeeder)->run();
    $term = fn (string $name): int => (int) array_search($name, app(Vocabularies::class)->options(Post::CATEGORY), true);
    $this->research = $term('Research & Innovation');
    $this->campus = $term('Campus News');

    $this->engineering = Faculty::factory()->create(['short_name' => 'FE', 'name' => ['en' => 'Engineering']]);
    $this->cse = Department::factory()->create(['faculty_id' => $this->engineering->id, 'short_name' => 'CSE']);
    $this->engine = app(RelationEngine::class);

    $this->robot = Post::factory()->create(['title' => ['en' => 'CSE robot wins in Tokyo'], 'active_at' => '2026-05-01 10:00:00', 'author_label' => ['en' => 'Public Relations Office'],
        'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
    $this->robot->syncTerms(Post::CATEGORY, [$this->research]);
    $this->engine->sync($this->robot, ['faculty.faculty:'.$this->engineering->id, 'department.department:'.$this->cse->id], mainSite: false, user: null);

    $this->convocation = Post::factory()->create(['title' => ['en' => 'Convocation 2026 held'], 'active_at' => '2026-04-15 10:00:00']);
    $this->convocation->syncTerms(Post::CATEGORY, [$this->campus]);
    $this->engine->sync($this->convocation, [], mainSite: true, highlights: ['site:0' => ['order' => 1, 'until' => '2026-05-09 23:00:00']], user: null);

    $this->lab = Post::factory()->create(['title' => ['en' => 'New CSE lab opens'], 'active_at' => '2026-03-20 10:00:00']);
    $this->engine->sync($this->lab, ['department.department:'.$this->cse->id], mainSite: true, highlights: ['site:0' => ['order' => 2]], user: null);
});

afterEach(fn () => Carbon::setTestNow());

it('schedules with active_at, not published_at (FR-NEWS-01, AC-NEWS-02)', function () {
    expect(PublishDate::column(Post::class))->toBe('active_at')
        ->and(Schema::hasColumn('news_posts', 'published_at'))->toBeFalse();

    $later = Post::factory()->scheduled('+1 day')->create(['title' => ['en' => 'Admission fair tomorrow']]);
    $this->engine->sync($later, [], mainSite: true, user: null);
    $this->get('/news')->assertOk()->assertDontSee('Admission fair tomorrow');
    $this->get($later->url())->assertNotFound();

    Carbon::setTestNow('2026-05-11 09:30:00');
    $this->get('/news')->assertOk()->assertSee('Admission fair tomorrow');

    expect(Post::factory()->create()->active_at)->not->toBeNull();   // empty "show from" = now
});

it('shows a post in its entities, and on the main site only when the Admin put it there (AC-NEWS-01)', function () {
    $this->get('/news')->assertOk()
        ->assertSeeInOrder(['Convocation 2026 held', 'New CSE lab opens'])->assertDontSee('CSE robot wins in Tokyo');

    foreach ([$this->engineering, $this->cse] as $entity) {
        $base = rtrim($entity->url(), '/');
        $this->get($base.'/news')->assertOk()->assertSee('CSE robot wins in Tokyo');
        $this->get($base.'/news/'.$this->robot->slug())->assertOk()->assertSee('Public Relations Office');
    }
    $this->get(rtrim($this->engineering->url(), '/').'/news')->assertSee('New CSE lab opens');   // CSE is below Engineering
    $this->get('/news/'.$this->robot->slug())->assertNotFound();                                    // not on the main site
    expect($this->robot->url())->toBeIn(collect([$this->engineering, $this->cse])->map(fn ($e) => rtrim($e->url(), '/').'/news/'.$this->robot->slug())->all());   // under one of its places
});

it('keeps the main-site switch away from a department user (AC-NEWS-03)', function () {
    $user = makeUser(['ViewAny:Post', 'View:Post', 'Create:Post', 'Update:Post'], [$this->cse]);
    $this->actingAs($user);

    Livewire::test(CreatePost::class)->assertFormFieldIsHidden('main_site')
        ->fillForm(['title' => ['en' => 'CSE seminar'], 'show_in' => ['department.department:'.$this->cse->id], 'main_site' => true])
        ->call('create')->assertHasNoFormErrors();
    expect(Post::query()->get()->first(fn (Post $p): bool => $p->title === 'CSE seminar')->isOnMainSite())->toBeFalse();
});

it('drops an ended highlight from Featured News but keeps the post in /news (AC-NEWS-04, FR-NEWS-04)', function () {
    $featured = (new FeaturedNewsSection)->items(new SectionContext('site:0', ['count' => 5], 'en'));
    expect($featured->pluck('title')->all())->toBe(['New CSE lab opens']);                         // the convocation's highlight ended yesterday
    $this->get('/news')->assertSee('Convocation 2026 held');

    $latest = (new LatestNewsSection)->items(new SectionContext('site:0', ['source_mode' => 'latest', 'count' => 5], 'en'));
    expect($latest->pluck('title')->all())->toBe(['Convocation 2026 held', 'New CSE lab opens']);

    $byCategory = (new NewsByCategorySection)->items(new SectionContext('department.department:'.$this->cse->id, ['categories' => [$this->research, $this->campus], 'count' => 3], 'en'));
    expect($byCategory)->toHaveCount(1)->and($byCategory[0]['posts']->pluck('title')->all())->toBe(['CSE robot wins in Tokyo']);

    $html = view(FeaturedNewsSection::view(), ['items' => $featured, 'section' => null, 'settings' => ['display' => 'slider']])->render();
    expect($html)->toContain('New CSE lab opens')->toContain('scroll-snap-type');
});

it('filters /news by category, tag and month (FR-NEWS-05)', function () {
    $this->get('/news?category=campus-news')->assertOk()->assertSee('Convocation 2026 held')->assertDontSee('New CSE lab opens');
    $this->get('/news?year=2026&month=3')->assertOk()->assertSee('New CSE lab opens')->assertDontSee('Convocation 2026 held');
    $this->get('/news')->assertSeeInOrder(['Archive', 'April 2026', 'March 2026']);

    Vocabularies::ensureTerms(Vocabularies::SHARED_TAGS, [['name' => ['en' => 'Convocation'], 'slug' => 'convocation']]);
    $tag = (int) array_search('Convocation', app(Vocabularies::class)->options(Vocabularies::SHARED_TAGS), true);
    $this->convocation->syncTerms(Vocabularies::SHARED_TAGS, [$tag]);
    $this->get('/news?tag=convocation')->assertOk()->assertSee('Convocation 2026 held')->assertDontSee('New CSE lab opens');
});

it('shows a post with its video, in the chosen detail layout, and counts the view (FR-NEWS-02/03)', function () {
    $base = rtrim($this->cse->url(), '/');
    $this->get($base.'/news/'.$this->robot->slug(), ['User-Agent' => 'Mozilla/5.0'])->assertOk()
        ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
        ->assertSee('"@type":"NewsArticle"', false)
        ->assertSee('news-post--standard', false)
        ->assertSee('New CSE lab opens');                                                            // more news of this place

    settings()->save('layouts', ['news.detail' => ['layout' => 'full_width']]);
    $this->get($base.'/news/'.$this->robot->slug())->assertOk()->assertDontSee('news-post--standard', false);

    expect(app(ViewCounter::class)->flush())->toBe(1);
});

it('lists main-site news in the page block and in search, sitemap with their own dates', function () {
    expect(NewsBlock::posts(['categories' => [$this->campus]])->pluck('title')->all())->toBe(['Convocation 2026 held'])
        ->and(NewsBlock::posts(['limit' => 5])->pluck('title')->all())->not->toContain('CSE robot wins in Tokyo');

    app(SearchIndex::class)->refresh($this->convocation->getMorphClass(), $this->convocation->id);
    expect(DB::table('core_search_index')->where('searchable_type', 'news.post')->where('searchable_id', $this->convocation->id)->value('published_at'))
        ->toStartWith('2026-04-15');                                                                 // the kernel reads active_at
    expect(collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' '))->toContain('/news/'.$this->convocation->slug());
});

it('shows DIU News: "Latest" with the two newest, cards, "All News" with a search and the categories, a post with its hits and three more (Phase 4.5d)', function () {
    $this->get('/news')->assertOk()
        ->assertSeeInOrder(['Latest', 'Stay updated with DIU', 'Convocation 2026 held', 'Campus News', 'Apr 15, 2026', 'New CSE lab opens', 'All News', 'Search', 'Filter by Category', 'Campus News']);
    $this->get('/news?category=campus-news')->assertOk()->assertDontSee('Stay updated with DIU')->assertSeeInOrder(['Convocation 2026 held', 'Campus News'])->assertDontSee('New CSE lab opens');

    $this->get($this->convocation->url())->assertOk()
        ->assertSeeInOrder(['News', 'Convocation 2026 held', 'Campus News', 'Apr 15, 2026', 'Hit:', 'Explore the Inspiring Stories and Events of DIU Campus', 'More News', 'New CSE lab opens']);

    // The module's own pages (any theme without its version) keep their layouts
    expect(moduleView('News', 'show', ['post' => $this->convocation, 'related' => collect(), 'layout' => 'standard', 'base' => '']))->toContain('max-width:860px');

    settings()->saveFor(new NewsSettings, ['per_page' => 12, 'show_author' => true, 'latest_title' => ['en' => 'Newsroom'], 'all_title' => ['en' => 'Every Story']]);
    $this->get('/news')->assertSee('Newsroom')->assertSee('Every Story')->assertDontSee('All News');
});
