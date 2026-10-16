<?php

namespace Database\Factories;

use App\Models\Gadget;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gadget_id' => Gadget::factory(),
            'quantity' => 1,
            'unit_price' => fn (array $attributes) => Gadget::find($attributes['gadget_id'])->price,
            'purchase_date' => now()->toDateString(),
            'archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived' => true,
        ]);
    }
}
