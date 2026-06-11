<?php

use App\Livewire\Products\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->create();
    $this->category = Category::factory()->create();
});

test('admin can view products page', function () {
    $this->actingAs($this->admin)
        ->get(route('products.index'))
        ->assertOk();
});

test('worker cannot access products page', function () {
    $this->actingAs($this->worker)
        ->get(route('products.index'))
        ->assertForbidden();
});

test('guest is redirected from products page', function () {
    $this->get(route('products.index'))
        ->assertRedirect(route('login'));
});

test('admin can create a product', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Wireless Mouse')
        ->set('sku', 'WM-0001')
        ->set('categoryId', $this->category->id)
        ->set('minStock', 5)
        ->call('save')
        ->assertSet('showModal', false);

    expect(Product::where('sku', 'WM-0001')->exists())->toBeTrue();
});

test('sku is stored in uppercase', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Test Product')
        ->set('sku', 'wm-0002')
        ->set('categoryId', $this->category->id)
        ->call('save');

    expect(Product::where('sku', 'WM-0002')->exists())->toBeTrue();
});

test('name, sku and category are required', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', '')
        ->set('sku', '')
        ->set('categoryId', null)
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'sku' => 'required', 'categoryId' => 'required']);
});

test('sku must be unique', function () {
    Product::factory()->create(['sku' => 'DUPE-01', 'category_id' => $this->category->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Another Product')
        ->set('sku', 'DUPE-01')
        ->set('categoryId', $this->category->id)
        ->call('save')
        ->assertHasErrors(['sku' => 'unique']);
});

test('admin can edit a product', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openEdit', $product)
        ->assertSet('editingId', $product->id)
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertSet('showModal', false);

    expect($product->fresh()->name)->toBe('Updated Name');
});

test('editing a product keeps its own sku valid', function () {
    $product = Product::factory()->create(['sku' => 'KEEP-01', 'category_id' => $this->category->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openEdit', $product)
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();
});

test('admin can delete a product', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $product);

    expect(Product::find($product->id))->toBeNull();
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
});

test('products are searchable by name and sku', function () {
    Product::factory()->create(['name' => 'Wireless Mouse', 'sku' => 'WM-001', 'category_id' => $this->category->id]);
    Product::factory()->create(['name' => 'Keyboard', 'sku' => 'KB-001', 'category_id' => $this->category->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'Wireless')
        ->assertSee('Wireless Mouse')
        ->assertDontSee('Keyboard');
});

test('admin can upload an image when creating a product', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('product.jpg', 200, 200);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Camera')
        ->set('sku', 'CAM-001')
        ->set('categoryId', $this->category->id)
        ->set('image', $file)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('sku', 'CAM-001')->first();
    expect($product->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image_path);
});

test('uploaded image replaces old image on edit', function () {
    Storage::fake('public');

    $old = UploadedFile::fake()->image('old.jpg');
    $oldPath = $old->store('products', 'public');

    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'image_path' => $oldPath,
    ]);

    $newFile = UploadedFile::fake()->image('new.jpg', 300, 300);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openEdit', $product)
        ->set('image', $newFile)
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($product->fresh()->image_path);
});

test('image must be jpeg png or webp — gif is rejected', function () {
    Storage::fake('public');

    // GIF is a valid image but not in our whitelist (jpeg/png/webp)
    $file = UploadedFile::fake()->image('product.gif')->mimeType('image/gif');

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Bad Upload')
        ->set('sku', 'BAD-001')
        ->set('categoryId', $this->category->id)
        ->set('image', $file)
        ->call('save')
        ->assertHasErrors(['image']);
});

test('image max size is 2mb', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('big.jpg')->size(3000);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Big Image')
        ->set('sku', 'BIG-001')
        ->set('categoryId', $this->category->id)
        ->set('image', $file)
        ->call('save')
        ->assertHasErrors(['image']);
});

test('products can be filtered by category', function () {
    $other = Category::factory()->create();
    Product::factory()->create(['name' => 'Product A', 'category_id' => $this->category->id]);
    Product::factory()->create(['name' => 'Product B', 'category_id' => $other->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('filterCategory', $this->category->id)
        ->assertSee('Product A')
        ->assertDontSee('Product B');
});

test('search combined with category filter does not leak other categories', function () {
    $other = Category::factory()->create();
    Product::factory()->create(['name' => 'Wireless Mouse', 'sku' => 'WM-100', 'category_id' => $this->category->id]);
    // Name matches the search but belongs to another category — without a
    // grouped OR the name-match would bypass the category filter.
    Product::factory()->create(['name' => 'Wireless Keyboard', 'sku' => 'WK-200', 'category_id' => $other->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'Wireless')
        ->set('filterCategory', $this->category->id)
        ->assertSee('Wireless Mouse')
        ->assertDontSee('Wireless Keyboard');
});
