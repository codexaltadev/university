<?php

namespace Modules\Faq\Freshness;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Modules\Faq\Models\Faq;
use Platform\Notifications\FreshnessSource;

/** Content Update Reminder (FR-NOTI-10): FAQs shown in an entity ("Show in"), by last change. */
class FaqFreshness extends FreshnessSource
{
    public static function module(): string
    {
        return 'faq';
    }

    public static function model(): string
    {
        return Faq::class;
    }

    public static function label(): string
    {
        return 'FAQ';
    }

    public function editUrl(Model $record): ?string
    {
        return Route::has('filament.admin.resources.faqs.edit') ? route('filament.admin.resources.faqs.edit', $record) : null;
    }
}
