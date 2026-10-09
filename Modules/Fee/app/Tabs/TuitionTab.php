<?php

namespace Modules\Fee\Tabs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Fee\Models\Tuition;
use Platform\Website\EntityTab;

/** "Tuition Fees" on the program page (FR-FEE-04 through FR-PROG-18): one block per fee category. */
class TuitionTab extends EntityTab
{
    public static function key(): string
    {
        return 'fee.tuition';
    }

    public static function label(): string
    {
        return __('fee::fee.tuition_fees');
    }

    public static function view(): string
    {
        return 'fee::tabs.tuition';
    }

    public static function sort(): int
    {
        return 30;
    }

    public function visible(Model $entity): bool
    {
        return $this->tuitions($entity)->isNotEmpty();
    }

    public function data(Model $entity): array
    {
        return ['tuitions' => $this->tuitions($entity)];
    }

    /** @var array<int, Collection<int, Tuition>> per program, for visible() + data() of one page */
    protected array $loaded = [];

    /** @return Collection<int, Tuition> */
    protected function tuitions(Model $program): Collection
    {
        return $this->loaded[$program->getKey()] ??= Tuition::query()->visible()->where('program_id', $program->getKey())
            ->with(['category', 'lines'])->ordered()->get()
            ->sortBy(fn (Tuition $t): int => (int) $t->category?->sort_order)->values();
    }
}
