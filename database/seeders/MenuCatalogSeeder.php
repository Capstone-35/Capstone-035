<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuCatalogSeeder extends Seeder
{
    /**
     * Real 774 HUB menu catalog (10 categories, 44 items).
     * Idempotent: safely updates/restores real items and archives obsolete dummy items
     * without breaking historical order_items foreign keys.
     */
    public function run(): void
    {
        $categoriesData = [
            ['name' => 'Espresso', 'slug' => 'espresso', 'sort_order' => 1],
            ['name' => '774 Series', 'slug' => '774-series', 'sort_order' => 2],
            ['name' => 'Cold Extraction', 'slug' => 'cold-extraction', 'sort_order' => 3],
            ['name' => 'Mocktails', 'slug' => 'mocktails', 'sort_order' => 4],
            ['name' => 'Blend & Smoothies', 'slug' => 'blend-smoothies', 'sort_order' => 5],
            ['name' => 'Others', 'slug' => 'others', 'sort_order' => 6],
            ['name' => 'Matcha & Milk', 'slug' => 'matcha-milk', 'sort_order' => 7],
            ['name' => 'Main Course', 'slug' => 'main-course', 'sort_order' => 8],
            ['name' => 'Snacks', 'slug' => 'snacks', 'sort_order' => 9],
            ['name' => 'Pastry', 'slug' => 'pastry', 'sort_order' => 10],
        ];

        $categories = [];
        $activeCategorySlugs = [];

        foreach ($categoriesData as $cData) {
            $activeCategorySlugs[] = $cData['slug'];

            $category = Category::withTrashed()->firstOrNew(['slug' => $cData['slug']]);
            $category->fill([
                'name' => $cData['name'],
                'sort_order' => $cData['sort_order'],
                'is_active' => true,
                'is_demo' => false,
            ]);
            $category->deleted_at = null;
            $category->save();

            $categories[$cData['slug']] = $category;
        }

        $productsData = [
            // (001) ESPRESSO
            ['cat' => 'espresso', 'name' => 'Espresso', 'price' => 15000, 'desc' => 'Ekstraksi espresso murni house blend 774 HUB.'],
            ['cat' => 'espresso', 'name' => 'Black', 'price' => 24000, 'desc' => 'Espresso dengan air mineral (Americano / Long Black).'],
            ['cat' => 'espresso', 'name' => 'Cappuccino', 'price' => 25000, 'desc' => 'Espresso dengan steamed milk dan foam susu lembut.'],
            ['cat' => 'espresso', 'name' => 'Cafe Latte', 'price' => 26000, 'desc' => 'Espresso lembut dipadukan dengan susu segar creamy.'],
            ['cat' => 'espresso', 'name' => 'Magic', 'price' => 28000, 'desc' => 'Double ristretto dengan rasio susu velvety khas Melbourne.'],
            ['cat' => 'espresso', 'name' => 'Split', 'price' => 30000, 'desc' => 'Sajian satu shot espresso dan satu gelas milk coffee.'],
            ['cat' => 'espresso', 'name' => 'Dirty Latte', 'price' => 28000, 'desc' => 'Espresso panas dituang langsung di atas susu dingin tanpa es.'],
            ['cat' => 'espresso', 'name' => 'Caramel Macchiato', 'price' => 28000, 'desc' => 'Espresso dengan susu creamy dan manisnya saus karamel.'],

            // (774) SERIES
            ['cat' => '774-series', 'name' => 'Original (Palm Sugar)', 'price' => 20000, 'desc' => '774 Series signature coffee dengan palm sugar.'],
            ['cat' => '774-series', 'name' => 'Butterscotch (Best Seller)', 'price' => 23000, 'desc' => 'Best seller 774 Series dengan cita rasa butterscotch creamy.', 'featured' => true],
            ['cat' => '774-series', 'name' => 'Herbs', 'price' => 23000, 'desc' => '774 Series dengan homemade herbs extract.'],
            ['cat' => '774-series', 'name' => 'Caramel Almond', 'price' => 23000, 'desc' => '774 Series dengan karakter rasa classic nutty caramel almond.'],
            ['cat' => '774-series', 'name' => 'Chai', 'price' => 23000, 'desc' => '774 Series dengan perpaduan aroma tea-herbs.'],
            ['cat' => '774-series', 'name' => 'Earl Grey', 'price' => 23000, 'desc' => '774 Series dengan citrusy aroma khas Earl Grey.'],

            // (003) COLD EXTRACTION
            ['cat' => 'cold-extraction', 'name' => 'Classic Cold Brew', 'price' => 23000, 'desc' => 'Kopi seduh dingin klasik dengan karakter halus dan bersih.'],
            ['cat' => 'cold-extraction', 'name' => 'Mont Blanc OG', 'price' => 28000, 'desc' => 'Cold extraction signature dengan krim lembut beraroma sitrus.'],
            ['cat' => 'cold-extraction', 'name' => 'Bright Citrus Cold Brew', 'price' => 25000, 'desc' => 'Cold brew segar dengan sentuhan rasa sitrus yang cerah.'],
            ['cat' => 'cold-extraction', 'name' => 'Chai Cold Foam', 'price' => 28000, 'desc' => 'Cold extraction dengan lapisan cold foam rempah chai.'],
            ['cat' => 'cold-extraction', 'name' => 'Earl Grey Cold Foam', 'price' => 28000, 'desc' => 'Cold extraction dengan lapisan cold foam beraroma Earl Grey.'],

            // (004) MOCKTAILS
            ['cat' => 'mocktails', 'name' => 'Guillermo', 'price' => 28000, 'desc' => 'Mocktail kopi segar dengan sentuhan jeruk nipis.'],
            ['cat' => 'mocktails', 'name' => 'Arancio', 'price' => 28000, 'desc' => 'Mocktail kopi segar berpadu rasa jeruk sitrus yang seimbang.'],

            // (005) BLEND & SMOOTHIES
            ['cat' => 'blend-smoothies', 'name' => 'Biscoff Frappe', 'price' => 30000, 'desc' => 'Minuman blend creamy dengan biskuit Lotus Biscoff.'],
            ['cat' => 'blend-smoothies', 'name' => 'Caramel Frappe', 'price' => 30000, 'desc' => 'Minuman blend es lembut dengan saus karamel gurih manis.'],
            ['cat' => 'blend-smoothies', 'name' => 'St. Ruby', 'price' => 30000, 'desc' => 'Smoothies signature 774 HUB bernuansa buah segar.', 'featured' => true],
            ['cat' => 'blend-smoothies', 'name' => 'St. Spinel', 'price' => 30000, 'desc' => 'Smoothies signature 774 HUB segar dan creamy.'],

            // (006) OTHERS
            ['cat' => 'others', 'name' => 'Lychee Tea', 'price' => 23000, 'desc' => 'Teh dingin menyegarkan dengan rasa buah leci.'],
            ['cat' => 'others', 'name' => 'Strawberry Tea', 'price' => 23000, 'desc' => 'Teh dingin segar dengan paduan rasa stroberi manis asam.'],
            ['cat' => 'others', 'name' => 'Lemonade', 'price' => 25000, 'desc' => 'Minuman lemon segar buatan rumah.'],
            ['cat' => 'others', 'name' => 'Honey Lemonade', 'price' => 26000, 'desc' => 'Perpaduan lemon segar dengan manis alami madu.'],
            ['cat' => 'others', 'name' => 'Mineral Water', 'price' => 8000, 'desc' => 'Air mineral botol.'],

            // (007) MATCHA & MILK
            ['cat' => 'matcha-milk', 'name' => 'Matcha', 'price' => 25000, 'desc' => 'Matcha premium dipadukan dengan susu segar.'],
            ['cat' => 'matcha-milk', 'name' => 'Earl Grey Milk', 'price' => 23000, 'desc' => 'Teh Earl Grey beraroma bergamot dengan susu creamy.', 'featured' => true],
            ['cat' => 'matcha-milk', 'name' => 'Thai Tea', 'price' => 23000, 'desc' => 'Teh khas Thailand yang manis dan creamy.'],
            ['cat' => 'matcha-milk', 'name' => 'Chocolate', 'price' => 25000, 'desc' => 'Minuman cokelat pekat dan lembut dengan susu segar.'],

            // (008) MAIN COURSE
            ['cat' => 'main-course', 'name' => 'Mie Laksa', 'price' => 23000, 'desc' => 'A bowl of laksa noodle with rich and savory broth topped with shrimp, seaweed, and egg.'],
            ['cat' => 'main-course', 'name' => 'Chicken Karaage Bowl', 'price' => 26000, 'desc' => 'Chicken karaage served with a half cooked sunny side up and steamed white rice.', 'featured' => true],
            ['cat' => 'main-course', 'name' => 'Ayam Rempah', 'price' => 28000, 'desc' => 'Spiced herbs marinated chicken wing served with our homemade sambal and steamed white rice.'],

            // (009) SNACKS
            ['cat' => 'snacks', 'name' => 'Chicken Popcorn', 'price' => 20000, 'desc' => 'Potongan ayam goreng krispi seukuran gigitan.'],
            ['cat' => 'snacks', 'name' => 'French Fries', 'price' => 20000, 'desc' => 'Kentang goreng renyah disajikan hangat.'],
            ['cat' => 'snacks', 'name' => 'Mix Platter', 'price' => 33000, 'desc' => 'Aneka camilan goreng pilihan cocok untuk dinikmati bersama.', 'featured' => true],
            ['cat' => 'snacks', 'name' => 'Pisang Goreng', 'price' => 20000, 'desc' => 'Pisang goreng hangat renyah di luar, lembut di dalam.'],

            // (010) PASTRY
            ['cat' => 'pastry', 'name' => 'Croissant Almond', 'price' => 25000, 'desc' => 'Croissant renyah berlapis dengan isian dan taburan almond panggang.'],
            ['cat' => 'pastry', 'name' => 'Pain au Chocolate', 'price' => 25000, 'desc' => 'Pastry croissant dengan isian cokelat leleh di dalamnya.'],
            ['cat' => 'pastry', 'name' => 'Triple Cheese', 'price' => 25000, 'desc' => 'Pastry gurih dengan perpaduan tiga lapis keju melimpah.'],
        ];

        $activeProductSlugs = [];

        foreach ($productsData as $p) {
            $cat = $categories[$p['cat']];
            $slug = Str::slug($p['name']);
            $activeProductSlugs[] = $slug;

            $product = Product::withTrashed()
                ->where('slug', $slug)
                ->orWhere(function ($q) use ($cat, $p) {
                    $q->where('category_id', $cat->id)->where('name', $p['name']);
                })
                ->first();

            if (! $product) {
                $product = new Product;
            }

            $product->fill([
                'category_id' => $cat->id,
                'name' => $p['name'],
                'slug' => $slug,
                'price' => $p['price'],
                'description' => $p['desc'],
                'image_path' => "/images/products/{$slug}.svg",
                'is_available' => true,
                'is_featured' => (bool) ($p['featured'] ?? false),
                'is_active' => true,
                'is_demo' => false,
            ]);
            $product->deleted_at = null;
            $product->save();
        }

        // Archive obsolete products not in the real menu (preserves order_items FK integrity)
        Product::query()
            ->whereNotIn('slug', $activeProductSlugs)
            ->each(function (Product $oldProduct) {
                $oldProduct->update([
                    'is_available' => false,
                    'is_featured' => false,
                    'is_active' => false,
                ]);
                $oldProduct->delete();
            });

        // Archive obsolete categories not in the real menu
        Category::query()
            ->whereNotIn('slug', $activeCategorySlugs)
            ->each(function (Category $oldCategory) {
                $oldCategory->update([
                    'is_active' => false,
                ]);
                $oldCategory->delete();
            });
    }
}
