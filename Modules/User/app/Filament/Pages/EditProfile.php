<?php

namespace Modules\User\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

/** Profile with the admin interface language (UI/UX Part A §9). */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            Select::make('locale')->label(__('user::user.fields.locale'))
                ->options(fn (): array => collect(config('platform.locales'))->mapWithKeys(fn (string $l): array => [$l => __("user::user.locales.{$l}")])->all())
                ->required()
                ->selectablePlaceholder(false),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }

    /** The new language applies right away, not on the next page. */
    protected function getRedirectUrl(): ?string
    {
        return static::getUrl();
    }
}
