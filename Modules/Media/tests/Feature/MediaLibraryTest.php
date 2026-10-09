<?php

/*
| Phase 2.5b — Media Library (C-07): upload checks, conversions, scope, usage,
| picker, replace, private files, screen (FR-MEDIA-01..16, AC-MEDIA-01..04).
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Media\Filament\Resources\MediaItems\MediaActions;
use Modules\Media\Filament\Resources\MediaItems\MediaItemResource;
use Modules\Media\Filament\Resources\MediaItems\Pages\EditMediaItem;
use Modules\Media\Filament\Resources\MediaItems\Pages\ListMediaItems;
use Modules\Office\Filament\Resources\Offices\Pages\CreateOffice;
use Modules\Office\Filament\Resources\Offices\Pages\EditOffice;
use Modules\Office\Models\Office;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Media\MediaFolder;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
use Platform\Media\MediaRejected;
use Platform\Modules\ModulePermissions;
use Platform\Settings\Setting;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Media');
    Permission::findOrCreate('ManageAll:MediaItem', 'web');
});

function library(): MediaLibrary
{
    return app(MediaLibrary::class);
}

function photo(string $name = 'Campus Photo.jpg', int $w = 1200, int $h = 800): UploadedFile
{
    return UploadedFile::fake()->image($name, $w, $h);
}

it('stores an upload with its facts, slug file name and WebP sizes (FR-MEDIA-11, BRL-MEDIA-03)', function () {
    $this->actingAs(superAdmin());
    $item = library()->store(photo());

    expect($item->kind)->toBe('image')
        ->and($item->mime_type)->toBe('image/jpeg')
        ->and([$item->width, $item->height])->toBe([1200, 800])
        ->and($item->file_hash)->toHaveLength(64)
        ->and($item->title)->toBe('Campus Photo')
        ->and([$item->scope_type, $item->scope_id])->toBe(['site', 0])
        ->and($item->fileName())->toBe('campus-photo.jpg');

    $file = $item->file();
    expect($file->model_type)->toBe('core.media-item')
        ->and($file->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($file->hasGeneratedConversion('large'))->toBeTrue()
        ->and($item->url('thumb'))->toEndWith('.webp');
});

it('refuses scripts, even disguised as pictures (FR-MEDIA-13, AC-MEDIA-03)', function (string $name, string $content) {
    $this->actingAs(superAdmin());

    expect(fn () => library()->store(UploadedFile::fake()->createWithContent($name, $content)))->toThrow(MediaRejected::class);
    expect(MediaItem::count())->toBe(0);
})->with([
    'php file' => ['shell.php', '<?php system($_GET["c"]);'],
    'php renamed to jpg' => ['photo.jpg', '<?php system($_GET["c"]);'],
    'php inside a real jpeg' => ['photo.jpg', (function () {
        $img = imagecreatetruecolor(10, 10);
        ob_start();
        imagejpeg($img);

        return ob_get_clean().'<?php echo 1; ?>';
    })()],
    'text pretending to be png' => ['notes.png', 'just some text'],
    'html page' => ['page.html', '<html><script>alert(1)</script></html>'],
    'type not allowed in settings' => ['tool.exe', 'MZ'],
]);

it('cleans SVG files of scripts and event handlers', function () {
    $this->actingAs(superAdmin());
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script><a href="javascript:alert(3)"><circle r="5"/></a></svg>';

    $item = library()->store(UploadedFile::fake()->createWithContent('logo.svg', $svg));
    $stored = Storage::disk('public')->get($item->file()->getPathRelativeToRoot());

    expect($item->kind)->toBe('image')
        ->and($stored)->not->toContain('script')->not->toContain('onload')->not->toContain('javascript')
        ->and($stored)->toContain('<circle');
});

it('shows scoped users their entities\', their own and shared media only (FR-MEDIA-14, AC-MEDIA-04)', function () {
    $engineering = faculty();
    $cse = department($engineering, 'CSE');
    $eee = department($engineering, 'EEE');
    $cseUser = makeUser(['ViewAny:MediaItem', 'View:MediaItem'], [$cse]);
    $eeeUser = makeUser(['ViewAny:MediaItem', 'Create:MediaItem'], [$eee]);

    $this->actingAs($eeeUser);
    $eeeOnly = library()->store(photo('eee.jpg'));
    $eeeShared = library()->store(photo('eee-shared.jpg'), ['is_shared' => true]);
    expect([$eeeOnly->scope_type, $eeeOnly->scope_id])->toBe([$eee->getMorphClass(), $eee->id]);   // BRL-MEDIA-01

    $visible = fn ($user) => MediaItem::query()->visibleTo($user)->pluck('id')->all();

    expect($visible($cseUser))->toBe([$eeeShared->id])
        ->and($cseUser->can('view', $eeeOnly))->toBeFalse()
        ->and($visible($eeeUser))->toEqualCanonicalizing([$eeeOnly->id, $eeeShared->id])
        ->and($visible(makeUser(['ViewAny:MediaItem'])))->toHaveCount(2)                   // no scope: unrestricted
        ->and($visible(makeUser(['ManageAll:MediaItem'], [$cse])))->toHaveCount(2);
});

it('tracks usage, warns with links before deleting a used file (FR-MEDIA-09/10, AC-MEDIA-02)', function () {
    app(ModulePermissions::class)->sync('Office');
    $this->actingAs(superAdmin());
    $logo = library()->store(photo('logo.jpg'));
    $offices = collect(['Registrar', 'Accounts', 'Admission'])->map(fn ($name) => Office::create(['name' => $name]));
    $offices->each->syncMedia('logo', [$logo->id]);

    expect($logo->refresh()->usage_count)->toBe(3);
    $warning = MediaActions::deleteWarning($logo)->toHtml();
    expect($warning)->toContain('Registrar')->toContain('Accounts')->toContain('Admission')->toContain('/admin/offices/');

    $logo->delete();                                                                          // soft delete still allowed
    expect($offices[0]->firstMedia('logo'))->toBeNull()
        ->and($offices[0]->mediaIdsIn('logo'))->toBe([$logo->id]);                           // usage kept for restore

    $offices[0]->forceDelete();
    expect($logo->refresh()->usage_count)->toBe(2);
});

it('picks an existing picture in a form, no new upload (FR-MEDIA-04, AC-MEDIA-01)', function () {
    app(ModulePermissions::class)->sync('Office');
    $this->actingAs(superAdmin());
    $logo = library()->store(photo('logo.jpg'));

    Livewire::test(CreateOffice::class)
        ->fillForm(['name' => 'Library Office', 'logo' => $logo->id])
        ->call('create')
        ->assertHasNoFormErrors();

    $office = Office::where('name', 'Library Office')->first();
    expect($office->firstMedia('logo')?->id)->toBe($logo->id)
        ->and(MediaItem::count())->toBe(1);

    Livewire::test(EditOffice::class, ['record' => $office->getRouteKey()])->assertFormSet(['logo' => $logo->id]);
});

it('replaces the file keeping the item and its link (FR-MEDIA-07)', function () {
    $this->actingAs(superAdmin());
    $item = library()->store(photo('old.jpg', 300, 200));
    $link = $item->permalink();
    $hash = $item->file_hash;

    library()->replace($item, photo('new.jpg', 640, 480));

    expect($item->refresh()->permalink())->toBe(str_replace('old.jpg', 'new.jpg', $link))
        ->and($item->width)->toBe(640)
        ->and($item->file_hash)->not->toBe($hash)
        ->and($item->getMedia('file'))->toHaveCount(1);

    $this->get(route('media.file', ['item' => $item->id]))->assertRedirect($item->file()->getUrl());
});

it('serves private files only through a signed link (BRL-MEDIA-02)', function () {
    $this->actingAs(superAdmin());
    $item = library()->store(UploadedFile::fake()->createWithContent('minutes.pdf', "%PDF-1.4\n%âãÏÓ\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"), ['is_private' => true]);

    expect($item->file()->disk)->toBe('local');
    $this->get(route('media.file', ['item' => $item->id]))->assertForbidden();
    $this->get($item->url())->assertOk();
});

it('lists, uploads and filters on screen (FR-MEDIA-01/03/05/15)', function () {
    $this->actingAs(makeUser())->get(MediaItemResource::getUrl('index'))->assertForbidden();
    $this->actingAs(makeUser(['ViewAny:MediaItem', 'View:MediaItem', 'Create:MediaItem', 'Update:MediaItem']));
    $this->get(MediaItemResource::getUrl('index'))->assertOk();

    Livewire::test(ListMediaItems::class)
        ->callAction('upload', data: ['files' => [photo('a.jpg'), photo('b.jpg')], 'is_shared' => true, 'alt_text' => ['en' => 'Students on campus', 'bn' => '']])
        ->assertNotified(__('media::media.notifications.uploaded', ['count' => 2]));

    expect(MediaItem::where('is_shared', true)->count())->toBe(2)
        ->and(MediaItem::pluck('alt_text')->all())->toBe(['Students on campus', 'Students on campus'])   // alt text at upload
        ->and(MediaItem::first()->getTranslations('alt_text'))->toBe(['en' => 'Students on campus']);   // empty language not stored
    $a = MediaItem::whereJsonContains('title->en', 'a')->first();

    Livewire::test(ListMediaItems::class)
        ->assertCanSeeTableRecords(MediaItem::all())
        ->set('display', 'list')
        ->searchTable('b')
        ->assertCanNotSeeTableRecords([$a]);

    Office::create(['name' => 'Registrar'])->syncMedia('logo', [$a->id]);
    Livewire::test(ListMediaItems::class)
        ->filterTable('unused')
        ->assertCanNotSeeTableRecords([$a]);

    $this->get(MediaItemResource::getUrl('edit', ['record' => $a]))->assertOk()->assertSee('Registrar');
});

it('browses folders like a file explorer (FR-MEDIA-08)', function () {
    $this->actingAs(superAdmin());
    $events = MediaFolder::create(['name' => 'Events']);
    $year = MediaFolder::create(['name' => '2025', 'parent_id' => $events->id]);
    $loose = library()->store(photo('loose.jpg'));
    $party = library()->store(photo('party.jpg'), ['folder_id' => $events->id]);
    $convocation = library()->store(photo('convocation.jpg'), ['folder_id' => $year->id]);

    $page = Livewire::test(ListMediaItems::class)
        ->assertSeeHtml('title="Events"')->assertDontSeeHtml('title="2025"')   // top level: folder tiles …
        ->assertCanSeeTableRecords([$loose])                          // … and files without a folder
        ->assertCanNotSeeTableRecords([$party, $convocation]);

    $page->call('openFolder', $events->id)
        ->assertSeeHtml('title="2025"')                                // sub-folder tile
        ->assertCanSeeTableRecords([$party])
        ->assertCanNotSeeTableRecords([$loose, $convocation]);

    // Upload and "New folder" land in the open folder
    $page->callAction('upload', data: ['files' => [photo('stage.jpg')]]);
    expect(MediaItem::whereJsonContains('title->en', 'stage')->first()->folder_id)->toBe($events->id);

    $page->call('showAll')->assertCanSeeTableRecords([$loose, $party, $convocation]);

    // Header button: every file at once, and back to the folder that was open
    $page->callAction('toggleBrowse')->assertSet('all', false)
        ->assertCanSeeTableRecords([$party])->assertCanNotSeeTableRecords([$loose]);
    $page->callAction('toggleBrowse')->assertSet('all', true)
        ->assertCanSeeTableRecords([$loose, $party, $convocation]);

    // Searching looks in every folder
    Livewire::test(ListMediaItems::class)
        ->searchTable('convocation')
        ->assertCanSeeTableRecords([$convocation])
        ->assertSeeText('2025');                                      // the card says which folder it is in

    // A folder the user cannot see falls back to the top level
    Livewire::withQueryParams(['folder' => 999])->test(ListMediaItems::class)->assertSet('folder', null);
});

it('lets the grid show 3 to 8 cards per row, actions as icons on the card', function () {
    $this->actingAs(superAdmin());
    $item = library()->store(photo());

    $page = Livewire::test(ListMediaItems::class)
        ->assertSeeHtml('uwms-media-grid')
        ->callAction('perRow8')
        ->assertSet('perRow', 8);

    expect($page->instance()->getTable()->getContentGrid())->toMatchArray(['xl' => 8])
        ->and($page->instance()->getTable()->getRecordActions()[0]->isIconButton())->toBeTrue();

    Livewire::withQueryParams(['perRow' => 50])->test(ListMediaItems::class)->assertSet('perRow', 5);   // only the offered choices
    Livewire::withQueryParams(['display' => 'list'])->test(ListMediaItems::class)
        ->assertDontSeeHtml('uwms-media-grid')
        ->assertActionHidden('perRow8');
});

it('replaces a file right from the grid, without opening its details', function () {
    $this->actingAs(superAdmin());
    $item = library()->store(photo('old.jpg', 300, 200));

    Livewire::test(ListMediaItems::class)
        ->assertSet('display', 'grid')
        ->callTableAction('replace', $item, data: ['file' => photo('new.jpg', 640, 480)])
        ->assertNotified(__('media::media.notifications.replaced'));

    expect($item->refresh()->width)->toBe(640)
        ->and($item->fileName())->toBe('new.jpg');
});

it('keeps folders with files in them (FR-MEDIA-08)', function () {
    $this->actingAs(superAdmin());
    $folder = MediaFolder::create(['name' => 'Events']);
    $item = library()->store(photo(), ['folder_id' => $folder->id]);

    expect(fn () => $folder->delete())->toThrow(ParentHasActiveChildren::class)
        ->and(MediaFolder::options(auth()->user()))->toBe([$folder->id => 'Events']);

    Livewire::test(EditMediaItem::class, ['record' => $item->getRouteKey()])
        ->fillForm(['folder_id' => null, 'title' => ['en' => 'Convocation', 'bn' => 'সমাবর্তন'], 'alt_text' => ['en' => 'Graduates']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($item->refresh()->getTranslation('title', 'bn'))->toBe('সমাবর্তন')
        ->and($item->alt_text)->toBe('Graduates')
        ->and($folder->delete())->toBeTrue();
});

it('uses library pictures as admin logo and default images (FR-SET-04/13)', function () {
    $this->actingAs(superAdmin());
    $logo = library()->store(photo('diu-logo.png'));

    settings()->saveFor(settings()->definition('admin_branding'), ['logo_light' => $logo->id]);

    $row = Setting::where('group', 'admin_branding')->where('key', 'logo_light')->first();
    expect($logo->refresh()->usage_count)->toBe(1)
        ->and($logo->usages()->first()->usable_type)->toBe('core.setting')
        ->and(settings()->media('admin_branding.logo_light')?->id)->toBe($logo->id);

    auth()->logout();
    $this->get('/admin/login')->assertOk()->assertSee($logo->url(), escape: false);

    $row->delete();
    expect($logo->refresh()->usage_count)->toBe(0);
});
