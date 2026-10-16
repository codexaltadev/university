<?php

namespace Modules\Links\Footer;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Modules\Links\Models\Link;
use Modules\Links\Sections\ImportantLinksSection;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Vocabularies;
use Platform\Website\FooterBlock;

/** Footer block "Important Links" (FR-LINK-02): the links of the site being shown — one group if chosen. */
class LinksFooterBlock extends FooterBlock
{
    public static function key(): string
    {
        return 'links.footer';
    }

    public static function label(): string
    {
        return __('links::links.footer.label');
    }

    public static function view(): string
    {
        return 'links::footer.block';
    }

    public static function schema(): array
    {
        return [
            Select::make('links_group')->label(__('links::links.fields.group'))->options(fn (): array => app(Vocabularies::class)->options(Link::GROUP))
                ->placeholder(__('links::links.all')),
            TextInput::make('links_count')->label(__('links::links.section.count'))->integer()->minValue(1)->maxValue(30)->default(8),
        ];
    }

    public function data(array $block, SiteContext $context): array
    {
        $key = $context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY;

        return ['links' => ImportantLinksSection::pick($key, $block['links_group'] ?? null, (int) ($block['links_count'] ?? 8) ?: 8)];
    }
}
