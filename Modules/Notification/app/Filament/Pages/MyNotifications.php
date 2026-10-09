<?php

namespace Modules\Notification\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Modules\Notification\Support\Notifier;
use Platform\Notifications\Notifications;

/**
 * User menu › Notifications (FR-NOTI-05): for every notification type, in the
 * bell and / or by email. Every signed-in user may set their own.
 */
class MyNotifications extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $slug = 'my-notifications';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function getTitle(): string
    {
        return __('notification::notification.my.title');
    }

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function mount(): void
    {
        $user = Auth::user();
        $notifier = app(Notifier::class);

        $this->form->fill(collect(app(Notifications::class)->types())
            ->mapWithKeys(fn (string $class, string $key): array => [self::field($key) => $notifier->channelsFor($user, $key, $class::defaultChannels())])
            ->all());
    }

    public function form(Schema $schema): Schema
    {
        $types = app(Notifications::class)->types();

        if ($types === []) {
            return $schema->components([Text::make(__('notification::notification.my.none'))]);
        }

        return $schema->statePath('data')->components([
            Section::make()->description(__('notification::notification.my.help'))->columns(2)->schema(
                collect($types)->map(fn (string $class, string $key) => CheckboxList::make(self::field($key))
                    ->label($class::label())
                    ->options(['database' => __('notification::notification.channels.database'), 'mail' => __('notification::notification.channels.mail')])
                    ->columns(2))->values()->all()
            ),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label(__('notification::notification.actions.save'))->submit('save')])->key('form-actions')]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $notifier = app(Notifier::class);

        foreach (array_keys(app(Notifications::class)->types()) as $key) {
            $notifier->setChannels(Auth::user(), $key, (array) ($state[self::field($key)] ?? []));
        }

        Notification::make()->success()->title(__('notification::notification.notifications.saved'))->send();
    }

    /** "inquiry.received" → "inquiry__received" (dots would nest the form state). */
    public static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }
}
