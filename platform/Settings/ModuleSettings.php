<?php

namespace Platform\Settings;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Str;

/**
 * One tab of Website Settings (FR-SET-10, Module Guide §12). A module registers it
 * with $r->settings(FaqSettings::class); the tab disappears while the module is
 * inactive (AC-SET-02). Field names are keys inside the group: settings('faq.per_page').
 */
abstract class ModuleSettings
{
    /** Module slug, or a core group (general, contact, social …). */
    abstract public function group(): string;

    /** @return array<Component|Field> */
    abstract public function schema(): array;

    public function label(): string
    {
        return Str::headline($this->group());
    }

    public function icon(): string|BackedEnum|null
    {
        return null;
    }

    /** Tab order; core groups use 10–90, module tabs come after. */
    public function sort(): int
    {
        return 100;
    }

    /** @return array<string, mixed> key => value used until someone saves the tab */
    public function defaults(): array
    {
        return [];
    }

    /**
     * Keys whose value is {"en": …, "bn": …} (FR-SET-11). Build their fields with
     * TranslatableTabs, named "key.{locale}".
     *
     * @return list<string>
     */
    public function translatable(): array
    {
        return [];
    }

    /** @return list<string> keys stored encrypted (passwords, secrets); never shown again after saving */
    public function encrypted(): array
    {
        return [];
    }

    /** @return list<string> keys the website and API never read (is_public = 0) */
    public function private(): array
    {
        return $this->encrypted();
    }

    /**
     * Keys holding Media Library ids (MediaPicker::make(...)->storeIds()); they are
     * recorded as usage, so the file warns before it is deleted.
     *
     * @return list<string>
     */
    public function media(): array
    {
        return [];
    }

    /** Extra rule on top of the Settings page permission (e.g. Super Admin only). */
    public function canAccess(User $user): bool
    {
        return true;
    }
}
