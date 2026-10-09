<?php

namespace Modules\Backup\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Backup\Jobs\RunBackup;
use Modules\Backup\Models\BackupRun;
use Modules\Backup\Support\BackupRunner;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Site › Backups (FR-BKP-02/06): health (last successful backup), "Backup now"
 * (database or full), the list of runs with size, result and download. Super
 * Admins by default (View / Create / Download:Backup).
 */
class ManageBackups extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?int $navigationSort = 95;

    protected static ?string $slug = 'backups';

    public static function getNavigationGroup(): ?string
    {
        return 'Site';
    }

    public static function getNavigationLabel(): string
    {
        return __('backup::backup.title');
    }

    public function getTitle(): string
    {
        return __('backup::backup.title');
    }

    public static function allows(string $permission): bool
    {
        $user = Auth::user();

        return $user !== null && ($user->isSuperAdmin() || $user->can($permission));
    }

    public static function canAccess(): bool
    {
        return static::allows('View:Backup');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function content(Schema $schema): Schema
    {
        $health = app(BackupRunner::class)->health();

        return $schema->components([
            Section::make(__('backup::backup.health.title'))
                ->icon($health['healthy'] ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle)
                ->iconColor($health['healthy'] ? 'success' : 'danger')
                ->schema([
                    Text::make($health['last']
                        ? __('backup::backup.health.last', ['when' => $health['last']->finished_at->diffForHumans(), 'type' => __("backup::backup.types.{$health['last']->type}"), 'size' => $health['last']->size()])
                        : __('backup::backup.health.none')),
                    Text::make($health['healthy'] ? __('backup::backup.health.ok') : __('backup::backup.health.warning'))
                        ->color($health['healthy'] ? 'success' : 'danger'),
                ]),
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('backupDatabase')->label(__('backup::backup.actions.database'))
                    ->action(fn () => $this->start('database')),
                Action::make('backupFull')->label(__('backup::backup.actions.full'))
                    ->action(fn () => $this->start('full')),
            ])->label(__('backup::backup.actions.now'))->icon(Heroicon::OutlinedPlay)->button()
                ->visible(fn (): bool => static::allows('Create:Backup')),
        ];
    }

    public function start(string $type): void
    {
        abort_unless(static::allows('Create:Backup'), 403);

        RunBackup::dispatch($type, Auth::id());
        Notification::make()->success()->title(__('backup::backup.notifications.started'))->body(__('backup::backup.notifications.started_help'))->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BackupRun::query()->with('user'))
            ->defaultSort('started_at', 'desc')
            ->poll('10s')
            ->columns([
                TextColumn::make('started_at')->label(__('backup::backup.fields.started_at'))->dateTime()->sortable(),
                TextColumn::make('type')->label(__('backup::backup.fields.type'))->badge()->color('gray')
                    ->formatStateUsing(fn (string $state): string => __("backup::backup.types.{$state}"))
                    ->description(fn (BackupRun $run): ?string => $run->reason),
                TextColumn::make('status')->label(__('backup::backup.fields.status'))->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success', 'failed' => 'danger', default => 'warning'
                    })
                    ->formatStateUsing(fn (string $state): string => __("backup::backup.status.{$state}"))
                    ->tooltip(fn (BackupRun $run): ?string => $run->error),
                TextColumn::make('size_bytes')->label(__('backup::backup.fields.size'))->formatStateUsing(fn (BackupRun $run): ?string => $run->size()),
                TextColumn::make('disk')->label(__('backup::backup.fields.disk'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')->label(__('backup::backup.fields.by'))->placeholder(__('backup::backup.scheduler')),
            ])
            ->recordActions([
                Action::make('download')->label(__('backup::backup.actions.download'))->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (BackupRun $run): bool => $run->status === BackupRun::SUCCESS && static::allows('Download:Backup'))
                    ->action(fn (BackupRun $run): ?StreamedResponse => $this->download($run)),
            ]);
    }

    /** Super Admin download only, through the app — never a public URL (BRL-BKP-01). */
    public function download(BackupRun $run): ?StreamedResponse
    {
        abort_unless(static::allows('Download:Backup'), 403);

        if (! $run->path || ! Storage::disk($run->disk)->exists($run->path)) {
            Notification::make()->danger()->title(__('backup::backup.notifications.missing'))->send();

            return null;
        }

        activity('backup')->performedOn($run)->event('downloaded')->log('backup downloaded');   // BRL-BKP-02

        return Storage::disk($run->disk)->download($run->path);
    }
}
