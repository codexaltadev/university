<?php

namespace Platform\Filament\Hub;

use Filament\Resources\Pages\ManageRelatedRecords;

/**
 * Hub part listing the entity's own records of another module, e.g. a Faculty's
 * departments (FR-GEN-86). Set $resource (the Hub's resource), $relationship and
 * $relatedResource: viewing needs the related resource's access, the scope filter of
 * the related model applies, and opening a row goes to the related resource.
 * "New" should pass the parent so it is preset (parentQuery()).
 */
abstract class HubRelationPage extends ManageRelatedRecords implements HubPage
{
    /** ?faculty=12 — what the related create page reads to preset and lock the parent. */
    abstract protected static function parentQueryKey(): string;

    /** @return array<string, int|string> */
    protected function parentQuery(): array
    {
        return [static::parentQueryKey() => $this->getOwnerRecord()->getKey()];
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
