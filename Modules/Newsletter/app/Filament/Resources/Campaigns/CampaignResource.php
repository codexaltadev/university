<?php

namespace Modules\Newsletter\Filament\Resources\Campaigns;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Newsletter\Filament\Resources\Campaigns\Pages\CreateCampaign;
use Modules\Newsletter\Filament\Resources\Campaigns\Pages\EditCampaign;
use Modules\Newsletter\Filament\Resources\Campaigns\Pages\ListCampaigns;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Support\CampaignSender;
use Platform\Localization\Languages;
use UnitEnum;

/**
 * Communication › Campaigns (FR-NLT-05/06): subject, language, body, lists, a time (or now);
 * "Send test", "Send" (now or at the time); the report — sent, failed, bounced. Sent ones are read-only; "Copy" starts a new one.
 */
class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $slug = 'newsletter-campaigns';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getModelLabel(): string
    {
        return __('newsletter::newsletter.campaign');
    }

    public static function getPluralModelLabel(): string
    {
        return __('newsletter::newsletter.campaigns');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return collect(Campaign::STATUSES)->mapWithKeys(fn (string $s): array => [$s => __('newsletter::newsletter.send_statuses.'.$s)])->all();
    }

    public static function form(Schema $schema): Schema
    {
        $locked = fn (?Campaign $record): bool => $record !== null && ! $record->isEditable();

        return $schema->components([
            Section::make()->columnSpanFull()->disabled($locked)->schema([
                TextInput::make('subject')->label(__('newsletter::newsletter.fields.subject'))->required()->maxLength(255),
                Grid::make(3)->schema([
                    Select::make('locale')->label(__('newsletter::newsletter.fields.locale'))->required()->native(false)
                        ->options(fn (): array => collect(app(Languages::class)->codes())->mapWithKeys(fn (string $c): array => [$c => strtoupper($c)])->all())
                        ->default(fn (): string => app(Languages::class)->default()),
                    Select::make('lists')->label(__('newsletter::newsletter.lists'))->relationship('lists', 'key')->multiple()->preload()->required()
                        ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name),
                    DateTimePicker::make('scheduled_at')->label(__('newsletter::newsletter.fields.scheduled_at'))->helperText(__('newsletter::newsletter.help.scheduled_at'))->seconds(false),
                ]),
                RichEditor::make('body')->label(__('newsletter::newsletter.fields.body'))->required()->fileAttachments(false),
                Grid::make(2)->schema([
                    TextInput::make('source_url')->label(__('newsletter::newsletter.fields.source_url'))->url()->maxLength(2048),
                    TextInput::make('source_label')->label(__('newsletter::newsletter.fields.source_label'))->maxLength(255),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('lists'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('subject')->label(__('newsletter::newsletter.fields.subject'))->searchable()->wrap()->description(fn (Campaign $record): string => $record->lists->pluck('key')->implode(', ')),
                TextColumn::make('send_status')->label(__('newsletter::newsletter.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success', 'sending', 'scheduled' => 'warning', 'failed' => 'danger', default => 'gray'
                    }),
                TextColumn::make('scheduled_at')->label(__('newsletter::newsletter.fields.scheduled_at'))->dateTime()->toggleable(),
                TextColumn::make('sent_count')->label(__('newsletter::newsletter.report.sent')),
                TextColumn::make('failed_count')->label(__('newsletter::newsletter.report.failed')),
                TextColumn::make('bounce_count')->label(__('newsletter::newsletter.report.bounced'))->toggleable(),
                TextColumn::make('sent_at')->label(__('newsletter::newsletter.fields.sent_at'))->dateTime()->toggleable(),
            ])
            ->filters([SelectFilter::make('send_status')->label(__('newsletter::newsletter.fields.status'))->options(self::statusOptions())])
            ->recordActions([
                self::testAction(),
                self::sendAction(),
                ReplicateAction::make()->label(__('newsletter::newsletter.copy'))
                    ->excludeAttributes(['send_status', 'sent_at', 'sent_count', 'failed_count', 'bounce_count', 'scheduled_at'])
                    ->after(fn (Campaign $replica, Campaign $record) => $replica->lists()->sync($record->lists()->pluck('newsletter_lists.id')))
                    ->successRedirectUrl(fn (Campaign $replica): string => self::getUrl('edit', ['record' => $replica])),
                EditAction::make()->visible(fn (Campaign $record): bool => $record->isEditable()),
                DeleteAction::make()->visible(fn (Campaign $record): bool => $record->send_status !== 'sending'),
            ]);
    }

    public static function testAction(): Action
    {
        return Action::make('test')->label(__('newsletter::newsletter.test.action'))->icon(Heroicon::OutlinedBeaker)->color('gray')
            ->schema([TextInput::make('email')->label(__('newsletter::newsletter.fields.email'))->email()->required()->default(fn (): ?string => auth()->user()?->email)])
            ->action(function (Campaign $record, array $data): void {
                app(CampaignSender::class)->test($record, (string) $data['email']);
                Notification::make()->success()->title(__('newsletter::newsletter.test.sent', ['email' => $data['email']]))->send();
            });
    }

    public static function sendAction(): Action
    {
        return Action::make('send')->label(__('newsletter::newsletter.send.action'))->icon(Heroicon::OutlinedPaperAirplane)->color('primary')
            ->visible(fn (Campaign $record): bool => $record->isEditable() && (bool) auth()->user()?->can('update', $record))
            ->requiresConfirmation()
            ->modalDescription(fn (Campaign $record): string => __($record->scheduled_at?->isFuture() ? 'newsletter::newsletter.send.confirm_later' : 'newsletter::newsletter.send.confirm_now', [
                'count' => number_format($record->audience()->count()), 'time' => $record->scheduled_at?->format('j M Y H:i'),
            ]))
            ->action(function (Campaign $record): void {
                if ($record->scheduled_at?->isFuture()) {
                    $record->forceFill(['send_status' => 'scheduled'])->save();
                    Notification::make()->success()->title(__('newsletter::newsletter.send.scheduled'))->send();

                    return;
                }
                $count = app(CampaignSender::class)->start($record);
                Notification::make()->success()->title(__('newsletter::newsletter.send.started', ['count' => number_format($count)]))->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }
}
