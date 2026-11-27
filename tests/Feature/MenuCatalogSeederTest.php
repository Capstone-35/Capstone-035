<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\MenuCatalogSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MenuCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_populates_exact_real_774_hub_menu_and_archives_obsolete_items(): void
    {
        // Seed an obsolete dummy category & product first
        $oldCategory = Category::create([
            'name' => 'Kopi Berbasis Espresso',
            'slug' => 'kopi-espresso',
            'sort_order' => 1,
            'is_active' => true,
            'is_demo' => true,
        ]);

        Product::create([
            'category_id' => $oldCategory->id,
            'name' => 'Kopi Susu Gula Aren 774',
            'slug' => 'kopi-susu-gula-aren-774',
            'price' => 22000,
            'is_available' => true,
            'is_active' => true,
            'is_demo' => true,
        ]);

        // Also seed a colliding slug ("caramel-macchiato") under old category
        Product::create([
            'category_id' => $oldCategory->id,
            'name' => 'Caramel Macchiato',
            'slug' => 'caramel-macchiato',
            'price' => 28000,
            'is_available' => true,
            'is_active' => true,
            'is_demo' => true,
        ]);

        // Run seeder twice to confirm full idempotency
        $this->seed(MenuCatalogSeeder::class);
        $this->seed(MenuCatalogSeeder::class);

        $categories = Category::query()->where('is_active', true)->orderBy('sort_order')->get();
        $this->assertCount(10, $categories);
        $this->assertSame([
            'Espresso',
            '774 Series',
            'Cold Extraction',
            'Mocktails',
            'Blend & Smoothies',
            'Others',
            'Matcha & Milk',
            'Main Course',
            'Snacks',
            'Pastry',
        ], $categories->pluck('name')->all());

        $products = Product::query()->where('is_active', true)->get();
        $this->assertCount(44, $products);

        // Spot-check exact prices across categories
        $this->assertSame(15000, Product::where('slug', 'espresso')->first()?->price);
        $this->assertSame(20000, Product::where('slug', 'original-palm-sugar')->first()?->price);
        $this->assertSame(23000, Product::where('slug', 'butterscotch-best-seller')->first()?->price);
        $this->assertSame(28000, Product::where('slug', 'mont-blanc-og')->first()?->price);
        $this->assertSame(28000, Product::where('slug', 'guillermo')->first()?->price);
        $this->assertSame(30000, Product::where('slug', 'biscoff-frappe')->first()?->price);
        $this->assertSame(8000, Product::where('slug', 'mineral-water')->first()?->price);
        $this->assertSame(25000, Product::where('slug', 'matcha')->first()?->price);
        $this->assertSame(23000, Product::where('slug', 'mie-laksa')->first()?->price);
        $this->assertSame(33000, Product::where('slug', 'mix-platter')->first()?->price);
        $this->assertSame(25000, Product::where('slug', 'triple-cheese')->first()?->price);

        // Colliding product moved cleanly to new Espresso category
        $espressoCat = Category::where('slug', 'espresso')->firstOrFail();
        $this->assertSame($espressoCat->id, Product::where('slug', 'caramel-macchiato')->first()?->category_id);

        // Obsolete dummy items archived cleanly
        $this->assertNull(Product::where('slug', 'kopi-susu-gula-aren-774')->first());
        $this->assertNull(Category::where('slug', 'kopi-espresso')->first());

        // Exact 5 curated Menu Andalan items
        $featuredSlugs = Product::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('id')
            ->pluck('slug')
            ->all();

        $this->assertEqualsCanonicalizing([
            'butterscotch-best-seller',
            'earl-grey-milk',
            'st-ruby',
            'chicken-karaage-bowl',
            'mix-platter',
        ], $featuredSlugs);

        // Customer /menu page renders exactly these 5 curated items in Menu Andalan & Promo
        $response = $this->get('/menu');
        $response->assertOk();
        $featuredProducts = $response->viewData('featuredProducts');
        $this->assertCount(5, $featuredProducts);
        $this->assertEqualsCanonicalizing([
            'butterscotch-best-seller',
            'earl-grey-milk',
            'st-ruby',
            'chicken-karaage-bowl',
            'mix-platter',
        ], $featuredProducts->pluck('slug')->all());
    }

    public function test_manager_can_upload_product_photo_and_it_renders_on_menu(): void
    {
        $this->seed([RolePermissionSeeder::class, MenuCatalogSeeder::class]);

        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole('manager');

        $product = Product::where('slug', 'butterscotch-best-seller')->firstOrFail();

        // 1x1 valid PNG binary
        $tinyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $uploaded = UploadedFile::fake()->createWithContent('butterscotch.png', $tinyPng);

        $response = $this->actingAs($manager)->patch(route('manager.products.update', ['product' => $product->id]), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'is_available' => '1',
            'is_featured' => '1',
            'is_active' => '1',
            'image' => $uploaded,
        ]);

        $response->assertRedirect(route('manager.products.index'));
        $product->refresh();

        $this->assertNotNull($product->imageUrl());
        $this->assertStringStartsWith('/images/products/', $product->image_path);

        try {
            $menuResp = $this->get('/menu');
            $menuResp->assertOk();
            $menuResp->assertSee($product->imageUrl(), false);
        } finally {
            $file = public_path(ltrim((string) $product->image_path, '/'));
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
