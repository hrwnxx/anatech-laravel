<?php

namespace Database\Seeders;

use App\Models\Gadget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class GadgetSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DatabaseSeeder::data('gadgets') as $row) {
            $stock = $row['stock'];
            unset($row['stock']);

            // Product photos are kept in database/seeders/images and copied
            // into storage/app/public/gadgets, where uploads are saved.
            if ($row['image']) {
                $path = 'gadgets/'.$row['image'];
                Storage::disk('public')->put($path, file_get_contents(database_path('seeders/images/'.$row['image'])));
                $row['image'] = $path;
            }

            $gadget = Gadget::forceCreate([
                ...$row,
                'description' => trim((string) $row['description']),
                'updated_at' => $row['created_at'],
            ]);

            // Every gadget gets exactly one inventory row holding its stock.
            $gadget->inventory()->create(['quantity' => $stock]);
        }
    }
}
