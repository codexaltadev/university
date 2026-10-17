<?php

namespace Modules\Newsletter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Newsletter\Models\Subscriber;

/** @extends Factory<Subscriber> */
class SubscriberFactory extends Factory
{
    protected $model = Subscriber::class;

    public function definition(): array
    {
        return ['email' => $this->faker->unique()->safeEmail(), 'status' => 'subscribed', 'verified_at' => now(), 'source' => 'admin', 'is_active' => true];
    }
}
