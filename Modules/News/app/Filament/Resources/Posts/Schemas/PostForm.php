<?php

namespace Modules\News\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\News\Models\Post;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Taxonomy\Vocabularies;

/**
 * Standard content form (UI/UX Part A §5.2): texts per language, picture, gallery and video on the left;
 * publishing (active, show from), address, Show in, highlight, category / tags, author and SEO on the right.
 */
class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("title.{$locale}")
                            ->label(__('news::news.fields.title'))
                            ->required($isDefault)
                            ->maxLength(255),
                        TextInput::make("subtitle.{$locale}")
                            ->label(__('news::news.fields.subtitle'))
                            ->maxLength(255),
                        Textarea::make("excerpt.{$locale}")
                            ->label(__('news::news.fields.excerpt'))
                            ->rows(3)
                            ->maxLength(500),
                        RichEditor::make("body.{$locale}")
                            ->label(__('news::news.fields.body'))
                            ->fileAttachments(false),
                    ]),
                    Section::make(__('news::news.sections.media'))->schema([
                        MediaPicker::make('featured_image')
                            ->label(__('news::news.fields.featured_image'))
                            ->image(),
                        MediaPicker::make('gallery')
                            ->label(__('news::news.fields.gallery'))
                            ->image()
                            ->multiple(),
                        TextInput::make('video_url')
                            ->label(__('news::news.fields.video_url'))
                            ->helperText(__('news::news.help.video_url'))
                            ->url()
                            ->maxLength(2048),
                    ])->collapsible(),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    WebAddressField::make(prefix: '/'.Post::PATH.'/'),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make(__('news::news.sections.classification'))->compact()->schema([
                        TermSelect::make('category')->vocabulary(Post::CATEGORY),
                        TermSelect::make('tags')->vocabulary(Vocabularies::SHARED_TAGS),
                    ]),
                    Section::make(__('news::news.sections.author'))->compact()->collapsible()->schema([
                        Select::make('author_user_id')
                            ->label(__('news::news.fields.author_user'))
                            ->relationship('authorUser', 'name')
                            ->searchable()
                            ->preload(),
                        ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                            TextInput::make("author_label.{$locale}")
                                ->label(__('news::news.fields.author_label')." ({$language})")
                                ->maxLength(100),
                        ]),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }
}
