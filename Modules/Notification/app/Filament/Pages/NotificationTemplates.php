<?php

namespace Modules\Notification\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Livewire\Attributes\Url;
use Modules\Notification\Models\Template;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Localization\Languages;
use Platform\Notifications\Notifications;

/**
 * Site › Notification templates (FR-NOTI-06): the wording of each notification
 * type per language, with its {placeholders}; "Use the default text" removes it.
 */
class NotificationTemplates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'notification-templates';

    #[Url]
    public ?string $type = null;

    public ?array $pick = [];

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Site';
    }

    public static function getNavigationLabel(): string
    {
        return __('notification::notification.templates.title');
    }

    public function getTitle(): string
    {
        return __('notification::notification.templates.title');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user !== null && ($user->isSuperAdmin() || $user->can('Manage:NotificationTemplate'));
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $types = app(Notifications::class)->types();
        $this->type = isset($types[$this->type]) ? $this->type : array_key_first($types);
        $this->pickForm->fill(['type' => $this->type]);
        $this->load();
    }

    public function load(): void
    {
        $class = $this->typeClass();

        if ($class === null) {
            return;
        }

        $template = Template::query()->where('key', $this->type)->first();
        $languages = app(Languages::class)->codes();

        $this->form->fill([
            'subject' => $template?->getTranslations('subject') ?: collect($languages)->mapWithKeys(fn (string $l): array => [$l => $class::defaultSubject($l)])->all(),
            'body' => $template?->getTranslations('body') ?: collect($languages)->mapWithKeys(fn (string $l): array => [$l => $class::defaultBody($l)])->all(),
            'is_active' => $template?->is_active ?? true,
        ]);
    }

    public function pickForm(Schema $schema): Schema
    {
        return $schema->statePath('pick')->components([
            Select::make('type')->label(__('notification::notification.templates.type'))
                ->options(collect(app(Notifications::class)->types())->map(fn (string $class): string => $class::label())->all())
                ->selectablePlaceholder(false)->live()
                ->afterStateUpdated(function (?string $state): void {
                    $this->type = $state;
                    $this->load();
                }),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $class = $this->typeClass();

        if ($class === null) {
            return $schema->components([Text::make(__('notification::notification.my.none'))]);
        }

        $placeholders = collect($class::placeholders())->map(fn (string $about, string $name): string => "{{$name}} — {$about}")->implode(' · ');

        return $schema->statePath('data')->components([
            Section::make($class::label())->description($placeholders ? __('notification::notification.templates.placeholders', ['list' => $placeholders]) : null)->schema([
                TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                    TextInput::make("subject.{$locale}")->label(__('notification::notification.templates.subject'))->required($isDefault)->maxLength(200),
                    Textarea::make("body.{$locale}")->label(__('notification::notification.templates.body'))->required($isDefault)->rows(6)->maxLength(5000),
                ]),
                Toggle::make('is_active')->label(__('notification::notification.templates.active'))
                    ->helperText(__('notification::notification.templates.active_help')),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('pickForm'),
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('notification::notification.actions.save'))->submit('save'),
                        Action::make('useDefault')->label(__('notification::notification.actions.use_default'))->color('gray')
                            ->requiresConfirmation()->action(fn () => $this->useDefault()),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess() && $this->typeClass(), 403);

        $state = $this->form->getState();
        $template = Template::query()->firstOrNew(['key' => $this->type]);
        $template->setTranslations('subject', array_filter((array) $state['subject']));
        $template->setTranslations('body', array_filter((array) $state['body']));
        $template->is_active = (bool) $state['is_active'];
        $template->save();

        Notification::make()->success()->title(__('notification::notification.notifications.saved'))->send();
    }

    public function useDefault(): void
    {
        abort_unless(static::canAccess(), 403);

        Template::query()->where('key', $this->type)->first()?->delete();
        $this->load();
        Notification::make()->success()->title(__('notification::notification.notifications.default_restored'))->send();
    }

    /** @return class-string|null */
    protected function typeClass(): ?string
    {
        return $this->type ? (app(Notifications::class)->types()[$this->type] ?? null) : null;
    }
}
