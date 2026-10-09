<?php

namespace Modules\Localization\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Platform\Localization\InterfaceTexts as Texts;
use Platform\Localization\Languages;

/** Site › Interface texts: change "Read more", "Apply now" … per language without code (FR-LANG-03). */
class InterfaceTexts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?int $navigationSort = 92;

    protected static ?string $slug = 'interface-texts';

    public static function getNavigationGroup(): ?string
    {
        return 'Site';
    }

    public static function getNavigationLabel(): string
    {
        return __('localization::localization.texts.title');
    }

    public function getTitle(): string
    {
        return __('localization::localization.texts.title');
    }

    public function getSubheading(): ?string
    {
        return __('localization::localization.texts.subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isSuperAdmin() || $user->can('Update:InterfaceText'));
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        $languages = app(Languages::class);

        return $table
            ->records(fn (?string $search): Collection => collect(app(Texts::class)->all())
                ->when(filled($search), fn (Collection $texts) => $texts->filter(
                    fn (array $t): bool => Str::contains(json_encode([$t['key'], $t['default'], $t['override']], JSON_UNESCAPED_UNICODE), $search, ignoreCase: true))))
            ->searchable()
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('key')->label(__('localization::localization.texts.key'))
                    ->description(fn (array $record): ?string => $record['namespace'] === '*' ? null : $record['namespace'])
                    ->fontFamily('mono')->size('sm'),
                ...collect($languages->labels())->map(fn (string $label, string $code): TextColumn => TextColumn::make("text_{$code}")
                    ->label($label)
                    ->state(fn (array $record): ?string => $record['override'][$code] ?? $record['default'][$code] ?? null)
                    ->description(fn (array $record): ?string => isset($record['override'][$code]) ? __('localization::localization.texts.changed') : null)
                    ->placeholder(__('localization::localization.texts.missing'))
                    ->wrap())->values()->all(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label(__('localization::localization.texts.edit'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading(fn (array $record): string => $record['key'])
                    ->schema(fn (array $record): array => collect($languages->labels(activeOnly: false))
                        ->map(fn (string $label, string $code): TextInput => TextInput::make($code)->label($label)
                            ->placeholder($record['default'][$code] ?? null)
                            ->helperText(__('localization::localization.texts.empty_uses_default')))
                        ->values()->all())
                    ->fillForm(fn (array $record): array => $record['override'])
                    ->action(function (array $record, array $data): void {
                        app(Texts::class)->save($record['namespace'], $record['key'], $data);
                        Notification::make()->success()->title(__('localization::localization.texts.saved'))->send();
                    }),
                Action::make('reset')
                    ->label(__('localization::localization.texts.reset'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->visible(fn (array $record): bool => $record['override'] !== [])
                    ->requiresConfirmation()
                    ->action(fn (array $record) => app(Texts::class)->save($record['namespace'], $record['key'], [])),
            ]);
    }
}
