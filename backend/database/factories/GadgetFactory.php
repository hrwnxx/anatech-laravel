<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Gadget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gadget>
 */
class GadgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'brand' => fake()->randomElement(['Apple', 'Samsung', 'Xiaomi', 'Oppo', 'Honor']),
            'gadget_name' => fake()->unique()->words(2, true),
            'model' => strtoupper(fake()->bothify('??-####')),
            'price' => fake()->randomFloat(2, 1000, 90000),
            'description' => fake()->sentence(),
            'archived' => false,
        ];
    }

    /**
     * Every gadget gets an inventory row, like in the real app.
     */
    public function configure(): static
    {
        return $this->afterCreating(
            fn (Gadget $gadget) => $gadget->inventory()->firstOrCreate([], ['quantity' => 10])
        );
    }

    public function withStock(int $quantity): static
    {
        return $this->afterCreating(
            fn (Gadget $gadget) => $gadget->inventory()->updateOrCreate([], ['quantity' => $quantity])
        );
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived' => true,
        ]);
    }
}
