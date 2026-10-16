<?php

namespace Modules\Administration\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;

/** @extends Factory<Member> */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'category_id' => fn (): int => AdministrationCategory::query()->firstOrCreate(
                ['group' => 'officer', 'sort_order' => 50],
                ['name' => ['en' => 'Officer'], 'is_public' => true, 'is_active' => true],
            )->id,
            'name' => ['en' => $this->faker->name()],
            'designation' => ['en' => $this->faker->jobTitle()],
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+880 1'.$this->faker->numerify('#########'),
            'is_active' => true,
        ];
    }

    public function in(AdministrationCategory $category): static
    {
        return $this->state(['category_id' => $category->id]);
    }
}
