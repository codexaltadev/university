<?php

namespace Platform\Filament\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Js;
use Livewire\Component;
use Platform\Media\MediaItem;

/**
 * HTML written visually or as source (Jodit): bold with Ctrl+B, tables, links, pasted HTML kept as
 * HTML, a "Source" button for the markup, and pictures / files from the Media Library — chosen or
 * uploaded right there, inserted with data-media-id so the block can count them as usage:
 *
 *     HtmlEditor::make('html')->contentCss([theme_asset('css/bootstrap-5.0.2.min.css')])
 *
 * The state is the HTML as written; the website cleans it when it shows it (HtmlBlock::clean()).
 */
class HtmlEditor extends Field implements HasEmbeddedView
{
    public const ASSETS = 'platform';

    /** @var array<string>|Closure */
    protected array|Closure $contentCss = [];

    protected int $height = 480;

    /** @param array<string>|Closure $urls stylesheets of the editing frame: the text looks as on the website */
    public function contentCss(array|Closure $urls): static
    {
        $this->contentCss = $urls;

        return $this;
    }

    public function height(int $pixels): static
    {
        $this->height = $pixels;

        return $this;
    }

    /** @return list<string> */
    public function getContentCss(): array
    {
        return array_values(array_filter((array) $this->evaluate($this->contentCss)));
    }

    /** @return array<Action> */
    public function getDefaultActions(): array
    {
        return [
            Action::make('insertMedia')
                ->label(__('platform::media.editor.insert'))
                ->modalHeading(__('platform::media.editor.insert'))
                ->modalWidth(Width::Large)
                ->modalSubmitActionLabel(__('platform::media.editor.insert_button'))
                ->schema([
                    MediaPicker::make('items')->label(__('platform::media.editor.files'))
                        ->helperText(__('platform::media.editor.files_help'))
                        ->multiple()->publicOnly()->storeIds()->required(),
                ])
                ->action(function (array $data, HtmlEditor $component, Component $livewire): void {
                    $livewire->dispatch('html-editor-insert', key: $component->getKey(), html: static::mediaHtml((array) $data['items']));
                }),
        ];
    }

    /**
     * Pictures as <img>, other files as links, in the order chosen. Addresses are site-relative
     * (they survive a domain change); a file link uses the permalink, which follows "replace file".
     *
     * @param  list<int|string>  $ids
     */
    public static function mediaHtml(array $ids): string
    {
        $ids = array_map('intval', $ids);

        return MediaItem::query()->whereKey($ids)->where('is_private', false)->with('media')->get()
            ->sortBy(fn (MediaItem $item): int => (int) array_search($item->id, $ids, true))
            ->map(function (MediaItem $item): string {
                $title = $item->title ?: (string) $item->fileName();
                $path = fn (?string $url): string => (string) (parse_url((string) $url, PHP_URL_PATH) ?: $url);

                if ($item->kind === 'image') {
                    $src = $item->isWithheld() ? $path($item->permalink()) : $path($item->url());

                    return '<p><img src="'.e($src).'" alt="'.e($item->alt_text ?: $title).'" data-media-id="'.$item->id.'" style="max-width:100%;height:auto"></p>';
                }

                return '<p><a href="'.e($path($item->permalink())).'" target="_blank" data-media-id="'.$item->id.'">'.e($title).'</a></p>';
            })
            ->implode('');
    }

    public function toEmbeddedHtml(): string
    {
        $statePath = $this->getStatePath();

        ob_start(); ?>

        <div
            id="<?= e($this->getId()) ?>"
            x-load
            x-load-src="<?= e(FilamentAsset::getAlpineComponentSrc('html-editor', self::ASSETS)) ?>"
            x-data="htmlEditorFormComponent({
                        state: $wire.<?= $this->applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) ?>,
                        key: <?= Js::from($this->getKey()) ?>,
                        script: <?= Js::from(FilamentAsset::getScriptSrc('jodit', self::ASSETS)) ?>,
                        style: <?= Js::from(FilamentAsset::getStyleHref('jodit', self::ASSETS)) ?>,
                        contentCss: <?= Js::from($this->getContentCss()) ?>,
                        height: <?= Js::from($this->height) ?>,
                        isDisabled: <?= Js::from($this->isDisabled()) ?>,
                        labels: <?= Js::from(['media' => __('platform::media.editor.insert')]) ?>,
                    })"
            x-on:html-editor-insert.window="insert($event.detail)"
            wire:ignore
            wire:key="<?= e($this->getLivewireKey()) ?>"
            class="fi-fo-html-editor"
        >
            <textarea x-ref="area" class="hidden"></textarea>
        </div>

        <?php $html = ob_get_clean();

        return $this->wrapEmbeddedHtml($html, labelTag: 'div');
    }
}
