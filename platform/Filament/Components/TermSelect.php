<?php

namespace Platform\Filament\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Platform\Localization\Languages;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * Category / tag field for any HasTaxonomy model (FR-TAX-04, Module Guide §8.3):
 *
 *     TermSelect::make('category')->vocabulary('faq.category')
 *
 * Single or multiple choice and the label come from the vocabulary; children are
 * indented under their parent. In a tag vocabulary (flat, multiple) typing a new
 * name creates the tag. The value is saved to core_termables, not to a column.
 */
class TermSelect extends Select
{
    protected ?string $vocabularyKey = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable()
            ->preload()
            ->dehydrated(false)
            ->afterStateHydrated(function (TermSelect $component, ?Model $record): void {
                if ($record === null || ! $record->exists) {
                    return;
                }

                $ids = $record->termIdsIn($component->getVocabularyKey());
                $component->state($component->isMultiple() ? $ids : ($ids[0] ?? null));
            })
            ->saveRelationshipsUsing(function (TermSelect $component, Model $record, mixed $state): void {
                $record->syncTerms($component->getVocabularyKey(), array_filter((array) $state));
            });
    }

    public function vocabulary(string $key): static
    {
        $this->vocabularyKey = $key;
        $vocabularies = app(Vocabularies::class);

        $this->label(fn (): string => $vocabularies->find($key)->name)
            ->multiple(fn (): bool => $vocabularies->find($key)->allows_multiple)
            ->options(fn (): array => $vocabularies->options($key));

        $vocabulary = $vocabularies->find($key);

        if (! $vocabulary->is_hierarchical && $vocabulary->allows_multiple) {
            $this->createOptionForm([
                TextInput::make('name')->label(__('platform::taxonomy.new_tag'))->required()->maxLength(100),
            ])->createOptionUsing(fn (array $data): int => Term::query()->create([
                'vocabulary_id' => $vocabularies->find($key)->id,
                'name' => [app(Languages::class)->default() => $data['name']],
            ])->getKey());
        }

        return $this;
    }

    public function getVocabularyKey(): string
    {
        return $this->vocabularyKey ?? $this->getName();
    }
}
