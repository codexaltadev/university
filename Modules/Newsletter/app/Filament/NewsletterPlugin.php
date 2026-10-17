<?php

namespace Modules\Newsletter\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Newsletter\Filament\Resources\Campaigns\CampaignResource;
use Modules\Newsletter\Filament\Resources\MailingLists\MailingListResource;
use Modules\Newsletter\Filament\Resources\Subscribers\SubscriberResource;

class NewsletterPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'newsletter';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CampaignResource::class, SubscriberResource::class, MailingListResource::class]);
    }

    public function boot(Panel $panel): void {}
}
