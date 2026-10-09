<?php

/*
| Phase 2.7d — Statistics (C-20): metrics per context with descendants, manual /
| override / rounding / digits, module off, refresh, screen, permissions (AC-STAT-01..05).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Faq\Models\Faq;
use Modules\Statistics\Database\Seeders\StatisticsDatabaseSeeder;
use Modules\Statistics\Filament\Pages\ManageStatistics;
use Platform\Context\MicrositeOwners;
use Platform\Localization\Language;
use Platform\Modules\ModuleManager;
use Platform\Relations\RelationEngine;
use Platform\Statistics\StatContext;
use Platform\Statistics\Statistics;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Permission::findOrCreate('Manage:Statistic', 'web');
    Permission::findOrCreate('ManageMain:Statistic', 'web');

    $this->engineering = faculty();
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');
    $this->k = fn ($m) => $m->getMorphClass().':'.$m->id;
    $this->stats = app(Statistics::class);

    $this->faqIn = function ($entity, bool $active = true): Faq {
        $faq = Faq::factory()->create(['is_active' => $active]);
        app(RelationEngine::class)->sync($faq, [($this->k)($entity)], user: null);

        return $faq;
    };
});

it('counts each context with what is below it, visible records only (AC-STAT-01/04, BRL-STAT-02)', function () {
    ($this->faqIn)($this->cse);
    ($this->faqIn)($this->cse);
    ($this->faqIn)($this->eee);
    ($this->faqIn)($this->engineering);
    ($this->faqIn)($this->cse, active: false);

    expect($this->stats->metric('faq.count', StatContext::site()))->toBe(4)
        ->and($this->stats->metric('faq.count', StatContext::of(($this->k)($this->engineering))))->toBe(4)
        ->and($this->stats->metric('faq.count', StatContext::of(($this->k)($this->cse))))->toBe(2);

    ($this->faqIn)($this->cse);                                                                     // FR-STAT-05: counted again
    expect($this->stats->metric('faq.count', StatContext::of(($this->k)($this->cse))))->toBe(3)
        ->and($this->stats->metric('faq.count', StatContext::site()))->toBe(5);
});

it('shows manual and automatic counters together, rounded, in the language\'s digits (AC-STAT-02, FR-STAT-06/09)', function () {
    ($this->faqIn)($this->cse);
    $set = StatSet::create(['name' => ['en' => 'Homepage']]);
    StatItem::create(['stat_set_id' => $set->id, 'label' => ['en' => 'Students', 'bn' => 'শিক্ষার্থী'], 'source' => 'manual', 'manual_value' => 22345, 'rounding' => 'thousand', 'sort_order' => 1]);
    StatItem::create(['stat_set_id' => $set->id, 'label' => ['en' => 'FAQs'], 'source' => 'metric', 'metric_key' => 'faq.count', 'sort_order' => 2]);
    StatItem::create(['stat_set_id' => $set->id, 'label' => ['en' => 'Satisfaction'], 'source' => 'metric', 'metric_key' => 'faq.count', 'override_value' => 98, 'suffix' => '%', 'sort_order' => 3]);
    StatItem::create(['stat_set_id' => $set->id, 'label' => ['en' => 'Empty'], 'source' => 'manual', 'sort_order' => 4]);   // no number: hidden

    expect(collect($this->stats->forOwner('site:0', 'default', 'en'))->pluck('display', 'label')->all())
        ->toBe(['Students' => '22,000+', 'FAQs' => '1', 'Satisfaction' => '98%']);

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bangla', 'native_name' => 'বাংলা', 'use_native_digits' => true]);
    expect($this->stats->forOwner('site:0', 'default', 'bn')[0])->toMatchArray(['label' => 'শিক্ষার্থী', 'display' => '২২,০০০+']);
});

it('hides a counter whose module is off, keeps the others (AC-STAT-03, FR-STAT-04)', function () {
    ($this->faqIn)($this->cse);
    $set = StatSet::create(['name' => ['en' => 'Homepage']]);
    $faqs = StatItem::create(['stat_set_id' => $set->id, 'label' => ['en' => 'FAQs'], 'source' => 'metric', 'metric_key' => 'faq.count']);
    StatItem::create(['stat_set_id' => $set->id, 'label' => ['en' => 'Students'], 'source' => 'manual', 'manual_value' => 100]);

    app(ModuleManager::class)->syncBundled();
    app(ModuleManager::class)->deactivate('faq');

    expect(collect($this->stats->forOwner('site:0'))->pluck('label')->all())->toBe(['Students'])
        ->and($this->stats->problem($faqs))->toBe('module_off');
});

it('creates the main site\'s default counters once', function () {
    (new StatisticsDatabaseSeeder)->run();
    (new StatisticsDatabaseSeeder)->run();

    expect(StatSet::count())->toBe(1)
        ->and(StatItem::pluck('metric_key')->filter()->values()->all())->toBe(['program.count', 'faculty-member.count', 'alumni.count']);
});

it('edits counters on screen with a live preview', function () {
    ($this->faqIn)($this->cse);
    $this->actingAs(superAdmin());
    $cse = ($this->k)($this->cse);

    $page = Livewire::withQueryParams(['owner' => $cse])->test(ManageStatistics::class)
        ->call('createSet');

    $page->set('data.items', [
        ['label' => ['en' => 'FAQs'], 'source' => 'metric', 'metric_key' => 'faq.count', 'rounding' => 'plus', 'is_active' => true],
        ['label' => ['en' => 'Students'], 'source' => 'manual', 'manual_value' => 1500, 'rounding' => 'hundred', 'is_active' => true],
    ])->assertSee('Now 1 → shows "1+"')
        ->call('save');

    expect(collect($this->stats->forOwner($cse, 'default', 'en'))->pluck('display', 'label')->all())
        ->toBe(['FAQs' => '1+', 'Students' => '1,500+']);
});

it('lets entity users edit only their entities\' counters (AC-STAT-05, BRL-STAT-01)', function () {
    $this->actingAs(makeUser())->get(ManageStatistics::getUrl())->assertForbidden();

    $user = makeUser(['Manage:Statistic'], [$this->cse]);
    expect(MicrositeOwners::keys($user, 'Statistic'))->toBe([($this->k)($this->cse)]);

    $this->actingAs($user);
    Livewire::withQueryParams(['owner' => ($this->k)($this->eee)])->test(ManageStatistics::class)
        ->assertSet('owner', ($this->k)($this->cse));                                                // not EEE, not the main site
});
