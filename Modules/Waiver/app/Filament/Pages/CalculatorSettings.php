<?php

namespace Modules\Waiver\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Modules\Waiver\Filament\Clusters\WaiverCalculator;
use Modules\Waiver\Settings\WaiverSettings;
use Modules\Waiver\Support\CalculatorWebsite;
use Platform\Settings\Settings;

/**
 * Academic › Waiver Calculator › Settings (UI/UX Part A §3): how matching rules combine and
 * the calculator's disclaimer — the same values as Website Settings' tab, for the Waiver
 * Office ("Manage:WaiverSettings").
 *
 * @property-read Schema $form
 */
class CalculatorSettings extends Page
{
    protected static ?string $cluster = WaiverCalculator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('waiver::waiver.settings.title');
    }

    public function getTitle(): string
    {
        return __('waiver::waiver.settings.title');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user !== null && (new WaiverSettings)->canAccess($user);
    }

    public function mount(): void
    {
        $this->form->fill(app(Settings::class)->group('waiver'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Group::make((new WaiverSettings)->schema())->columns(1),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))->submit('save')])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        app(Settings::class)->saveFor(new WaiverSettings, $this->form->getState());

        Notification::make()->success()->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label(__('waiver::waiver.preview'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.CalculatorWebsite::PAGE))->openUrlInNewTab(),
        ];
    }
}
