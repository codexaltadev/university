<?php

namespace Modules\Newsletter\Footer;

use Filament\Forms\Components\TextInput;
use Platform\Context\SiteContext;
use Platform\Website\FooterBlock;

/** Footer block "Newsletter" (FR-NLT-01): a short line and the email sign-up form. */
class SubscribeFooterBlock extends FooterBlock
{
    public static function key(): string
    {
        return 'newsletter.subscribe';
    }

    public static function label(): string
    {
        return __('newsletter::newsletter.footer.label');
    }

    public static function view(): string
    {
        return 'newsletter::footer.block';
    }

    public static function schema(): array
    {
        return [TextInput::make('newsletter_line')->label(__('newsletter::newsletter.footer.line'))->maxLength(200)->placeholder(__('newsletter::newsletter.footer.default_line'))];
    }

    public function data(array $block, SiteContext $context): array
    {
        return ['line' => filled($block['newsletter_line'] ?? null) ? $block['newsletter_line'] : __('newsletter::newsletter.footer.default_line')];
    }
}
