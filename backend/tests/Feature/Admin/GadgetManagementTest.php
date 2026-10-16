<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Gadget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GadgetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_admin_can_add_a_gadget_with_a_photo(): void
    {
        $category = Category::factory()->create(['name' => 'Tablet']);

        $response = $this->post('/api/admin/gadgets', [
            'category_id' => $category->id,
            'brand' => 'Apple',
            'gadget_name' => 'iPad Air 11"',
            'model' => 'iPad Air',
            'price' => 39900,
            'description' => 'M4 chip',
            'image' => $this->photo('ipad.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('gadget_name', 'iPad Air 11"')
            ->assertJsonPath('category', 'Tablet')
            ->assertJsonPath('stock', 0);  // new gadgets start with no stock

        $gadget = Gadget::firstOrFail();
        Storage::disk('public')->assertExists($gadget->image);
        $this->assertDatabaseHas('inventories', ['gadget_id' => $gadget->id, 'quantity' => 0]);
    }

    public function test_adding_a_gadget_validates_the_form(): void
    {
        $this->postJson('/api/admin/gadgets', ['price' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'brand', 'gadget_name', 'price']);
    }

    public function test_editing_without_a_new_photo_keeps_the_old_one(): void
    {
        $gadget = Gadget::factory()->create(['image' => 'gadgets/old.jpg']);

        $this->post("/api/admin/gadgets/{$gadget->id}", [
            'category_id' => $gadget->category_id,
            'brand' => 'Samsung',
            'gadget_name' => 'Renamed',
            'price' => 100,
        ])->assertOk()->assertJsonPath('gadget_name', 'Renamed');

        $this->assertSame('gadgets/old.jpg', $gadget->fresh()->image);
    }

    public function test_editing_with_a_new_photo_replaces_the_old_file(): void
    {
        Storage::disk('public')->put('gadgets/old.jpg', 'old');
        $gadget = Gadget::factory()->create(['image' => 'gadgets/old.jpg']);

        $this->post("/api/admin/gadgets/{$gadget->id}", [
            'category_id' => $gadget->category_id,
            'brand' => $gadget->brand,
            'gadget_name' => $gadget->gadget_name,
            'price' => $gadget->price,
            'image' => $this->photo('new.jpg'),
        ])->assertOk();

        Storage::disk('public')->assertMissing('gadgets/old.jpg');
        Storage::disk('public')->assertExists($gadget->fresh()->image);
    }

    public function test_archiving_moves_a_gadget_between_tabs(): void
    {
        $gadget = Gadget::factory()->create();

        $this->patchJson("/api/admin/gadgets/{$gadget->id}/archive")->assertOk()->assertJsonPath('archived', true);
        $this->getJson('/api/admin/gadgets')->assertJsonCount(0);
        $this->getJson('/api/admin/gadgets?archived=1')->assertJsonCount(1);
        $this->getJson('/api/gadgets')->assertJsonCount(0);  // gone from the public catalog

        $this->patchJson("/api/admin/gadgets/{$gadget->id}/unarchive")->assertOk()->assertJsonPath('archived', false);
        $this->getJson('/api/gadgets')->assertJsonCount(1);
    }

    public function test_stock_in_and_stock_out(): void
    {
        $gadget = Gadget::factory()->withStock(5)->create();

        $this->postJson("/api/admin/gadgets/{$gadget->id}/stock-in", ['quantity' => 10])
            ->assertOk()->assertJsonPath('stock', 15);

        $this->postJson("/api/admin/gadgets/{$gadget->id}/stock-out", ['quantity' => 4])
            ->assertOk()->assertJsonPath('stock', 11);
    }

    public function test_stock_can_never_go_below_zero(): void
    {
        $gadget = Gadget::factory()->withStock(3)->create();

        $this->postJson("/api/admin/gadgets/{$gadget->id}/stock-out", ['quantity' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertSame(3, $gadget->inventory->fresh()->quantity);
    }

    /**
     * A real product photo to upload. (UploadedFile::fake()->image() would
     * need PHP's GD extension, which XAMPP doesn't enable by default.)
     */
    private function photo(string $name): UploadedFile
    {
        $copy = tempnam(sys_get_temp_dir(), 'gadget');
        copy(database_path('seeders/images/gadget_6a67a48398e9c.jpg'), $copy);

        return new UploadedFile($copy, $name, 'image/jpeg', null, true);
    }
}
