<?php

namespace Modules\Media\Filament\Resources\MediaItems\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Modules\Media\Filament\Resources\MediaItems\MediaActions;
use Modules\Media\Filament\Resources\MediaItems\MediaItemResource;
use Platform\Media\MediaFolder;
use Platform\Media\MediaItem;

/**
 * Media Library as a file explorer (FR-MEDIA-01/08): breadcrumb and folder tiles on
 * top, the files of the open folder below. "All files" and searching look everywhere.
 */
class ListMediaItems extends ListRecords
{
    protected static string $resource = MediaItemResource::class;

    /** Grid (thumbnails) or list (FR-MEDIA-01); kept in the address. */
    #[Url]
    public string $display = 'grid';

    /** Cards per row in the grid on wide screens. */
    public const PER_ROW = [3, 4, 5, 6, 8];

    #[Url]
    public int $perRow = 5;

    /** Open folder; null = top level. */
    #[Url]
    public ?int $folder = null;

    /** Every file, whatever its folder. */
    #[Url]
    public bool $all = false;

    public function mount(): void
    {
        parent::mount();

        if (! in_array($this->perRow, self::PER_ROW, true)) {
            $this->perRow = 5;
        }

        // A folder the user may not see (or a deleted one) falls back to the top level
        if ($this->folder !== null && ! MediaFolder::query()->visibleTo(Auth::user())->whereKey($this->folder)->exists()) {
            $this->folder = null;
        }
    }

    /** The grid's card styles apply in grid view only. */
    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), ...($this->display === 'grid' ? ['uwms-media-grid'] : [])];
    }

    /**
     * Grid / list and cards per row change the table's columns and layout, which
     * Filament builds before an action runs: rebuild it so the change shows at once.
     */
    public function setView(?string $display = null, ?int $perRow = null): void
    {
        $this->display = $display ?? $this->display;
        $this->perRow = $perRow ?? $this->perRow;
        $this->resetTable();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['display', 'perRow'], true)) {
            $this->resetTable();
        }
    }

    public function openFolder(?int $folder): void
    {
        $this->folder = $folder;
        $this->all = false;
        $this->resetPage();
    }

    public function showAll(): void
    {
        $this->all = true;
        $this->resetPage();
    }

    /** Files of the open folder only, unless "All files" or a search is on. */
    public function isBrowsingFolder(): bool
    {
        return ! $this->all && blank($this->getTableSearch());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            // Lazy: the schema is built before wire:click changes the folder
            View::make('media::folder-bar')->viewData(fn (): array => $this->folderBar()),
            EmbeddedTable::make(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function folderBar(): array
    {
        $user = Auth::user();
        $current = $this->folder ? MediaFolder::query()->with('parent.parent.parent')->find($this->folder) : null;

        $folders = MediaFolder::query()->visibleTo($user)
            ->where('parent_id', $current?->id)
            ->withCount(['children', 'items' => fn ($q) => $q->visibleTo($user)])
            ->orderBy('name')
            ->get();

        return [
            'trail' => $current?->trail() ?? [],
            'folders' => $this->all ? collect() : $folders,
            'all' => $this->all,
            'searching' => filled($this->getTableSearch()),
            'filesHere' => MediaItem::query()->visibleTo($user)->where('folder_id', $current?->id)->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Folder by folder, or every file at once; the open folder is kept for the way back
            Action::make('toggleBrowse')
                ->label(fn (): string => __($this->all ? 'media::media.actions.by_folder' : 'media::media.browse.all_files'))
                ->icon(fn (): Heroicon => $this->all ? Heroicon::OutlinedFolder : Heroicon::OutlinedPhoto)
                ->color('gray')
                ->action(function (): void {
                    $this->all = ! $this->all;
                    $this->resetPage();
                }),
            Action::make('toggleLayout')
                ->label(fn (): string => __($this->display === 'grid' ? 'media::media.actions.list_view' : 'media::media.actions.grid_view'))
                ->icon(fn (): Heroicon => $this->display === 'grid' ? Heroicon::OutlinedListBullet : Heroicon::OutlinedSquares2x2)
                ->color('gray')
                ->action(fn () => $this->setView(display: $this->display === 'grid' ? 'list' : 'grid')),
            ActionGroup::make(collect(self::PER_ROW)->map(fn (int $n): Action => Action::make("perRow{$n}")
                ->label(trans_choice('media::media.per_row', $n, ['count' => $n]))
                ->icon(fn (): ?Heroicon => $this->perRow === $n ? Heroicon::Check : null)
                ->action(fn () => $this->setView(perRow: $n)))->all())
                ->label(fn (): string => trans_choice('media::media.per_row', $this->perRow, ['count' => $this->perRow]))
                ->icon(Heroicon::OutlinedViewColumns)
                ->button()
                ->color('gray')
                ->visible(fn (): bool => $this->display === 'grid'),
            MediaActions::folders(),
            MediaActions::upload(),
        ];
    }
}
