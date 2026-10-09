<?php

namespace Modules\Fee\Filament\Resources\WaiverRules\Pages\Concerns;

use Modules\Fee\Models\WaiverRule;

/** Waiver categories (in order) and genders are kept outside the model's attributes. */
trait SavesRuleConditions
{
    /** @var array{categories: list<int>, genders: list<string>} */
    protected array $conditions = ['categories' => [], 'genders' => []];

    protected function takeConditions(array $data): array
    {
        $this->conditions = ['categories' => array_values($data['waiver_category_ids'] ?? []), 'genders' => array_values($data['genders'] ?? [])];
        unset($data['waiver_category_ids'], $data['genders']);

        return $data;
    }

    protected function storeConditions(WaiverRule $rule): void
    {
        $rule->syncCategories($this->conditions['categories']);
        $rule->syncGenders($this->conditions['genders']);
    }
}
