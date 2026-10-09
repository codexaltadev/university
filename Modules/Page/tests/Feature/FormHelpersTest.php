<?php

/*
| Shared form helpers on the Page form (UI/UX Part A §6, §9): ●/○ on language tabs,
| "Missing বাংলা" list filter, alt-text warning (FR-GEN-61), web address field.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Page\Filament\Resources\Pages\Pages\CreatePage;
use Modules\Page\Filament\Resources\Pages\Pages\ListPages;
use Modules\Page\Models\Page;
use Platform\Media\MediaLibrary;

beforeEach(function () {
    Storage::fake('public');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(superAdmin());
});

it('marks language tabs written ● or not yet ○', function () {
    Livewire::test(CreatePage::class)
        ->fillForm(['title' => ['en' => 'About']])
        ->assertSeeHtml('●')
        ->assertSeeHtml('○')
        ->fillForm(['title' => ['en' => 'About', 'bn' => 'পরিচিতি'], 'excerpt' => ['bn' => 'সংক্ষেপ']])
        ->assertDontSeeHtml('○');
});

it('lists records missing a language', function () {
    $both = Page::factory()->create(['title' => ['en' => 'History', 'bn' => 'ইতিহাস']]);
    $englishOnly = Page::factory()->create(['title' => ['en' => 'Mission']]);

    Livewire::test(ListPages::class)
        ->filterTable('missing_title', 'bn')
        ->assertCanSeeTableRecords([$englishOnly])
        ->assertCanNotSeeTableRecords([$both]);
});

it('warns when a chosen picture has no alternative text (FR-GEN-61)', function () {
    $library = app(MediaLibrary::class);
    $bare = $library->store(UploadedFile::fake()->image('bare.jpg'));
    $described = $library->store(UploadedFile::fake()->image('ok.jpg'), ['alt_text' => ['en' => 'Students in the library']]);

    Livewire::test(CreatePage::class)
        ->fillForm(['featured_image' => $bare->id])
        ->assertSee(__('platform::media.no_alt', ['count' => 1]))
        ->fillForm(['featured_image' => $described->id])
        ->assertDontSee(__('platform::media.no_alt', ['count' => 1]));
});
