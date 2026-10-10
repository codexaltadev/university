<?php

namespace Modules\HeaderFooter\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Modules\HeaderFooter\Models\Region;
use Modules\HeaderFooter\Support\Regions;
use Platform\Context\FallbackResolver;
use Platform\Context\FollowsWorkingIn;
use Platform\Context\MicrositeOwners;
use Platform\Context\MicrositeTool;
use Platform\Filament\Components\FallbackIndicator;
use Platform\Filament\Components\TranslatableTabs;

/**
 * Appearance › Header & Footer (C-09): pick an owner and a region (top bar, header,
 * footer). Without its own, a place shows where it comes from, with "create own" and
 * "copy … and edit"; its own region has a theme variant, switches or footer columns,
 * and the copyright line.
 */
class ManageHeaderFooter extends Page implements MicrositeTool
{
    use FollowsWorkingIn;

    public const BLOCKS = ['about_text', 'menu', 'contact', 'social', 'newsletter', 'custom_html'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'header-footer';

    #[Url]
    public ?string $owner = null;

    #[Url]
    public string $region = 'header';

    public ?array $pick = [];

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Appearance';
    }

    public static function getNavigationLabel(): string
    {
        return __('headerfooter::headerfooter.title');
    }

    public function getTitle(): string
    {
        return __('headerfooter::headerfooter.title');
    }

    /** Microsite tool (UI/UX Part A §4.1): the entity editors' "Microsite" button opens it on one owner. */
    public static function canUseFor(string $ownerKey): bool
    {
        return in_array($ownerKey, MicrositeOwners::keys(Auth::user(), 'HeaderFooter'), true);
    }

    public static function urlFor(string $ownerKey): string
    {
        return static::getUrl(['owner' => $ownerKey]);
    }

    public static function isSetUpFor(string $ownerKey): bool
    {
        $regions = app(Regions::class);

        return $regions->own($ownerKey, 'header') !== null || $regions->own($ownerKey, 'footer') !== null;
    }

    public static function canAccess(): bool
    {
        return MicrositeOwners::keys(Auth::user(), 'HeaderFooter') !== [];
    }

    public function mount(): void
    {
        $owners = MicrositeOwners::keys(Auth::user(), 'HeaderFooter');
        abort_if($owners === [], 403);
        $this->owner = $this->ownerOnOpen($owners);   // "Working in" unless asked for
        $this->region = in_array($this->region, Region::REGIONS, true) ? $this->region : 'header';

        $this->pickForm->fill(['owner' => $this->owner, 'region' => $this->region]);
        $this->load();
    }

    public function pickForm(Schema $schema): Schema
    {
        return $schema->statePath('pick')->columns(2)->components([
            Select::make('owner')->label(__('platform::relations.owner'))
                ->options(fn (): array => MicrositeOwners::options(Auth::user(), 'HeaderFooter'))
                ->searchable()->selectablePlaceholder(false)->live()
                ->afterStateUpdated(function (?string $state): void {
                    if (in_array($state, MicrositeOwners::keys(Auth::user(), 'HeaderFooter'), true)) {
                        $this->owner = $state;
                        $this->load();
                    }
                }),
            Select::make('region')->label(__('headerfooter::headerfooter.region'))
                ->options(collect(Region::REGIONS)->mapWithKeys(fn (string $r): array => [$r => __("headerfooter::headerfooter.regions.{$r}")])->all())
                ->selectablePlaceholder(false)->live()
                ->afterStateUpdated(function (?string $state): void {
                    if (in_array($state, Region::REGIONS, true)) {
                        $this->region = $state;
                        $this->load();
                    }
                }),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            FallbackIndicator::make(
                from: fn (): ?string => $this->regions()->current($this->owner, $this->region)['from'],
                createOwn: fn () => $this->createOwn(false),
                copy: fn () => $this->createOwn(true),
            )->visible(fn (): bool => $this->ownRegion() === null),
            Section::make(fn (): string => __("headerfooter::headerfooter.regions.{$this->region}"))
                ->visible(fn (): bool => $this->ownRegion() !== null)
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('variant')->label(__('headerfooter::headerfooter.fields.variant'))
                            ->options(fn (): array => Regions::variants($this->region))
                            ->required()->selectablePlaceholder(false),
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
                    ]),
                    Group::make($this->topbarFields())->statePath('config')->visible(fn (): bool => $this->region === 'topbar'),
                    Group::make($this->headerFields())->statePath('config')->visible(fn (): bool => $this->region === 'header'),
                    Group::make($this->footerFields())->statePath('config')->visible(fn (): bool => $this->region === 'footer'),
                    TranslatableTabs::make()->schema(fn (string $locale): array => [
                        TextInput::make("copyright.{$locale}")->label(__('headerfooter::headerfooter.fields.copyright'))
                            ->helperText(__('headerfooter::headerfooter.help.copyright'))->maxLength(255),
                    ])->visible(fn (): bool => $this->region === 'footer'),
                ])
                ->footerActions([
                    Action::make('save')->label(__('headerfooter::headerfooter.actions.save'))->action(fn () => $this->save()),
                    Action::make('deleteOwn')->label(__('headerfooter::headerfooter.actions.delete_own'))
                        ->color('danger')->requiresConfirmation()
                        ->modalDescription(__('headerfooter::headerfooter.help.delete_own'))
                        ->action(fn () => $this->deleteOwn()),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('pickForm'), EmbeddedSchema::make('form')]);
    }

    /** FR-HF-02: what the top bar shows. */
    protected function topbarFields(): array
    {
        return [Grid::make(3)->schema(collect(['show_phone', 'show_email', 'show_quick_links', 'show_language_switcher', 'show_social', 'show_login'])
            ->map(fn (string $key): Toggle => Toggle::make($key)->label(__("headerfooter::headerfooter.fields.{$key}")))->all())];
    }

    /** Search, sticky header, the announcement / notice ticker slot (FR-HF-06). */
    protected function headerFields(): array
    {
        return [Grid::make(3)->schema(collect(['show_search', 'sticky', 'show_announcement_bar'])
            ->map(fn (string $key): Toggle => Toggle::make($key)->label(__("headerfooter::headerfooter.fields.{$key}")))->all())];
    }

    /** FR-HF-03: footer columns, each with blocks. */
    protected function footerFields(): array
    {
        $is = fn (string $type): \Closure => fn (Get $get): bool => $get('type') === $type;

        return [
            Repeater::make('columns')->label(__('headerfooter::headerfooter.fields.columns'))
                ->maxItems(6)->defaultItems(0)->reorderable()->collapsible()->grid(2)
                ->itemLabel(fn (array $state): ?string => collect((array) ($state['heading'] ?? []))->filter()->first())
                ->addActionLabel(__('headerfooter::headerfooter.actions.add_column'))
                ->schema([
                    ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                        TextInput::make("heading.{$locale}")->label(__('headerfooter::headerfooter.fields.heading')." ({$language})")->maxLength(100),
                    ]),
                    Repeater::make('blocks')->label(__('headerfooter::headerfooter.fields.blocks'))
                        ->defaultItems(1)->reorderable()->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['type']) ? __("headerfooter::headerfooter.blocks.{$state['type']}") : null)
                        ->schema([
                            Select::make('type')->label(__('headerfooter::headerfooter.fields.block'))
                                ->options(fn (): array => collect(static::BLOCKS)
                                    // Custom HTML can carry scripts: Super Admin only (like BRL-SET-02)
                                    ->reject(fn (string $b): bool => $b === 'custom_html' && ! Auth::user()?->isSuperAdmin())
                                    ->mapWithKeys(fn (string $b): array => [$b => __("headerfooter::headerfooter.blocks.{$b}")])->all())
                                ->required()->live()->selectablePlaceholder(false),
                            ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                                Textarea::make("text.{$locale}")->label(__('headerfooter::headerfooter.fields.text')." ({$language})")
                                    ->rows(3)->maxLength(1000)->visible($is('about_text')),
                            ]),
                            Select::make('menu_location')->label(__('headerfooter::headerfooter.fields.menu_location'))
                                // From the Menu module's configuration, not its classes (BRL-REL-02); empty while it is off
                                ->options(fn (): array => collect(config('menu.locations', []))->mapWithKeys(fn (string $l): array => [$l => __("menu::menu.locations.{$l}")])->all())
                                ->helperText(__('headerfooter::headerfooter.help.menu'))
                                ->visible($is('menu'))->required($is('menu')),
                            Textarea::make('html')->label(__('headerfooter::headerfooter.fields.html'))
                                ->rows(4)->visible($is('custom_html'))
                                ->disabled(fn (): bool => ! Auth::user()?->isSuperAdmin()),
                        ]),
                ]),
        ];
    }

    public function createOwn(bool $copy = false): void
    {
        $this->guardOwner();
        $current = $copy ? $this->regions()->current($this->owner, $this->region)['region'] : null;
        $this->regions()->create($this->owner, $this->region, $current instanceof Region ? $current : null);
        $this->load();

        Notification::make()->success()->title(__('headerfooter::headerfooter.notifications.created'))->send();
    }

    public function save(): void
    {
        $this->guardOwner();
        $region = $this->ownRegion() ?? abort(404);
        $state = $this->form->getState();

        // Custom HTML stays as it was unless a Super Admin changed it
        if (! Auth::user()?->isSuperAdmin()) {
            $state['config'] = $this->keepCustomHtml((array) $region->config, (array) ($state['config'] ?? []));
        }

        $region->fill([
            'variant' => $state['variant'],
            'is_active' => (bool) ($state['is_active'] ?? true),
            'config' => [...Regions::defaults($this->region), ...(array) ($state['config'] ?? [])],
        ]);
        $region->setTranslations('copyright', array_filter((array) ($state['copyright'] ?? []), fn ($t): bool => filled($t)));
        $region->save();
        app(FallbackResolver::class)->flush();

        $this->load();
        Notification::make()->success()->title(__('headerfooter::headerfooter.notifications.saved'))->send();
    }

    public function deleteOwn(): void
    {
        $this->guardOwner();

        if ($region = $this->ownRegion()) {
            $this->regions()->delete($region);
        }

        $this->load();
        Notification::make()->success()->title(__('headerfooter::headerfooter.notifications.deleted'))->send();
    }

    protected function load(): void
    {
        $region = $this->ownRegion();

        $this->form->fill($region ? [
            'variant' => $region->variant,
            'is_active' => $region->is_active,
            'config' => [...Regions::defaults($this->region), ...(array) $region->config],
            'copyright' => $region->getTranslations('copyright'),
        ] : []);
    }

    /** @param array<string, mixed> $old @param array<string, mixed> $new */
    protected function keepCustomHtml(array $old, array $new): array
    {
        $oldHtml = collect($old['columns'] ?? [])->flatMap(fn ($c) => collect($c['blocks'] ?? [])->where('type', 'custom_html'))->values();
        $i = 0;

        foreach ($new['columns'] ?? [] as $c => $column) {
            foreach ($column['blocks'] ?? [] as $b => $block) {
                if (($block['type'] ?? null) === 'custom_html') {
                    $new['columns'][$c]['blocks'][$b]['html'] = $oldHtml[$i++]['html'] ?? null;
                }
            }
        }

        return $new;
    }

    protected function guardOwner(): void
    {
        abort_unless(in_array($this->owner, MicrositeOwners::keys(Auth::user(), 'HeaderFooter'), true), 403);
    }

    protected function ownRegion(): ?Region
    {
        return $this->owner ? $this->regions()->own($this->owner, $this->region) : null;
    }

    protected function regions(): Regions
    {
        return app(Regions::class);
    }
}
