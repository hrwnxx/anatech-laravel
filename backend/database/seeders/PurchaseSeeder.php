<?php

namespace Database\Seeders;

use App\Models\Gadget;
use App\Models\Purchase;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $prices = Gadget::pluck('price', 'id');

        foreach (DatabaseSeeder::data('purchases') as $purchase) {
            Purchase::forceCreate([
                ...$purchase,
                // The old system didn't store the sale price, so use the gadget's current price.
                'unit_price' => $prices[$purchase['gadget_id']],
                'updated_at' => $purchase['created_at'],
            ]);
        }
    }
}
