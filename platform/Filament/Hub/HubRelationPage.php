<?php

namespace Platform\Filament\Hub;

use Filament\Resources\Pages\ManageRelatedRecords;

/**
 * Hub part listing the entity's own records, e.g. a Faculty's departments or a Program's
 * majors (FR-GEN-86). Set $resource (the Hub's resource) and $relationship; with
 * $relatedResource, viewing needs that resource's access, the related model's scope
 * filter applies and opening a row goes to it — "New" should then pass the parent
 * (parentQuery()) so the related create page presets and fixes it.
 */
abstract class HubRelationPage extends ManageRelatedRecords implements HubPage
{
    /** ?faculty=12 — what the related create page reads; null = records are made right here. */
    protected static function parentQueryKey(): ?string
    {
        return null;
    }

    /** @return array<string, int|string> */
    protected function parentQuery(): array
    {
        return static::parentQueryKey() === null ? [] : [static::parentQueryKey() => $this->getOwnerRecord()->getKey()];
    }

    public function getBreadcrumb(): string
    {
        return static::getNavigationLabel();
    }

    /** "FSIT — Departments" */
    public function getTitle(): string
    {
        return $this->getRecordTitle().' — '.static::getNavigationLabel();
    }
}
