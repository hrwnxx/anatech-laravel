<?php

namespace App\Models;

use Database\Factories\GadgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

#[Fillable(['category_id', 'brand', 'gadget_name', 'model', 'price', 'description', 'image', 'archived'])]
class Gadget extends Model
{
    /** @use HasFactory<GadgetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'archived' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Full URL of the product photo, or null when the gadget has none.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->image ? Storage::disk('public')->url($this->image) : null
        );
    }

    /**
     * Gadgets shown in the catalog and the admin "Active" tab.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where($query->qualifyColumn('archived'), false);
    }

    /**
     * Gadgets taken off the catalog but kept on record.
     */
    #[Scope]
    protected function archived(Builder $query): void
    {
        $query->where($query->qualifyColumn('archived'), true);
    }

    /**
     * Matches the keyword against brand, name, model or category name.
     */
    #[Scope]
    protected function search(Builder $query, ?string $keyword): void
    {
        if (blank($keyword)) {
            return;
        }

        $query->where(function (Builder $q) use ($keyword) {
            $q->where('brand', 'like', "%{$keyword}%")
                ->orWhere('gadget_name', 'like', "%{$keyword}%")
                ->orWhere('model', 'like', "%{$keyword}%")
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', "%{$keyword}%"));
        });
    }

    /**
     * Adds the numbers every gadget list needs in a single query:
     * category, current stock, units sold, average rating and review count.
     */
    #[Scope]
    protected function withStats(Builder $query): void
    {
        $query->with('category:id,name')
            ->withSum('inventory as stock', 'quantity')
            ->withSum(['purchases as units_sold' => fn (Builder $q) => $q->where('archived', false)], 'quantity')
            ->withAvg(['reviews as avg_rating' => fn (Builder $q) => $q->where('status', 'approved')], 'rating')
            ->withCount(['reviews as review_count' => fn (Builder $q) => $q->where('status', 'approved')]);
    }

    public function addStock(int $quantity): int
    {
        $inventory = $this->inventory()->firstOrCreate([], ['quantity' => 0]);
        $inventory->increment('quantity', $quantity);

        return $inventory->quantity;
    }

    /**
     * Takes units out of stock and returns what's left. Always call this
     * inside DB::transaction(): the row lock stops two requests from
     * selling the same last units at the same time.
     *
     * @throws ValidationException when there isn't enough stock
     */
    public function removeStock(int $quantity): int
    {
        $inventory = $this->inventory()->lockForUpdate()->first();
        $available = $inventory?->quantity ?? 0;

        if ($quantity > $available) {
            $name = "{$this->brand} {$this->gadget_name}";

            throw ValidationException::withMessages([
                'quantity' => $available === 0
                    ? "{$name} is out of stock."
                    : "{$name} only has {$available} in stock.",
            ]);
        }

        $inventory->decrement('quantity', $quantity);

        return $inventory->quantity;
    }
}
