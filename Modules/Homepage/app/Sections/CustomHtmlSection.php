<?php

namespace Modules\Homepage\Sections;

use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Auth;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/** Custom HTML (FR-HOME-02): can carry scripts, so only Super Admins write it (like BRL-SET-02). */
class CustomHtmlSection extends Section
{
    public static function key(): string
    {
        return 'core.html';
    }

    public static function label(): string
    {
        return __('homepage::homepage.sections.html');
    }

    public static function view(): string
    {
        return 'homepage::sections.html';
    }

    public static function settingsSchema(): array
    {
        return [
            Textarea::make('html')->label('HTML')->rows(6)
                ->disabled(fn (): bool => ! Auth::user()?->isSuperAdmin())
                ->helperText(__('homepage::homepage.help.html')),
        ];
    }

    public function items(SectionContext $ctx): ?string
    {
        return $ctx->setting('html');
    }
}
