<?php

namespace Modules\Gallery\Filament\Resources\Albums\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Gallery\Models\Video;
use Modules\Gallery\Support\Speakers;
use Platform\Filament\Components\PersonPicker;
use Platform\Filament\Components\TranslatableTabs;

/**
 * The album's YouTube / Vimeo / Facebook videos (FR-GAL-02/04): link, title per language,
 * episode, duration, speaker (Person Picker), dragged order. Uploaded videos are album items.
 */
class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('gallery::gallery.videos');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('url')->label(__('gallery::gallery.fields.video_url'))->helperText(__('gallery::gallery.help.video_url'))
                ->url()->required()->maxLength(2048)->columnSpanFull(),
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale): array => [
                TextInput::make("title.{$locale}")->label(__('gallery::gallery.fields.title'))->maxLength(255),
            ]),
            Grid::make(3)->columnSpanFull()->schema([
                TextInput::make('episode_no')->label(__('gallery::gallery.fields.episode_no'))->integer()->minValue(1)->maxValue(65535),
                TextInput::make('duration_seconds')->label(__('gallery::gallery.fields.duration'))->integer()->minValue(1)->suffix(__('gallery::gallery.seconds')),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]),
            PersonPicker::make('speaker')->label(__('gallery::gallery.fields.speaker'))->helperText(__('gallery::gallery.help.speaker'))->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('thumb')->label('')->imageSize(40)->state(fn (Video $record): ?string => $record->thumbnailUrl()),
                TextColumn::make('title')->label(__('gallery::gallery.fields.title'))->wrap()->placeholder(fn (Video $record): string => $record->url)
                    ->description(fn (Video $record): ?string => $record->speaker()?->name()),
                TextColumn::make('episode_no')->label(__('gallery::gallery.fields.episode_no')),
                TextColumn::make('provider')->label('')->badge()->color('gray'),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ])
            ->headerActions([
                CreateAction::make()->modalWidth('3xl')
                    ->using(fn (array $data): Video => DB::transaction(function () use ($data): Video {
                        $video = $this->getOwnerRecord()->videos()->create(Arr::except($data, ['speaker']));
                        app(Speakers::class)->set($video, PersonPicker::resolve($data['speaker'] ?? null));

                        return $video;
                    })),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('3xl')
                    ->mutateRecordDataUsing(fn (array $data, Video $record): array => $data + [
                        'speaker' => ($p = $record->assignments()->where('role_key', Video::ROLE)->first()?->person) ? PersonPicker::keyOf($p) : null,
                    ])
                    ->using(fn (Video $record, array $data): Video => DB::transaction(function () use ($record, $data): Video {
                        $record->update(Arr::except($data, ['speaker']));
                        app(Speakers::class)->set($record, PersonPicker::resolve($data['speaker'] ?? null));

                        return $record;
                    })),
                DeleteAction::make(),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('view', $ownerRecord);
    }

    protected function canCreate(): bool
    {
        return (bool) auth()->user()?->can('update', $this->getOwnerRecord());
    }

    protected function canEdit(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function canDelete(Model $record): bool
    {
        return $this->canCreate();
    }
}
