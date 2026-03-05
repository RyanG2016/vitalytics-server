<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\App;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class ProductMigrationSeeder extends Seeder
{
    /**
     * Migrate existing products and apps from config to database.
     */
    public function run(): void
    {
        $configProducts = config('vitalytics.products', []);

        foreach ($configProducts as $productSlug => $productData) {
            // Create or update product
            $product = Product::updateOrCreate(
                ['slug' => $productSlug],
                [
                    'name' => $productData['name'],
                    'description' => $productData['description'] ?? null,
                    'icon' => $productData['icon'] ?? 'fa-cube',
                    'color' => $productData['color'] ?? '#666666',
                    'is_active' => true,
                ]
            );

            $this->command->info("Product: {$product->name}");

            // Create apps
            foreach ($productData['sub_products'] ?? [] as $appIdentifier => $appData) {
                $apiKey = $appData['api_key'] ?? null;
                
                $app = App::updateOrCreate(
                    ['identifier' => $appIdentifier],
                    [
                        'product_id' => $product->id,
                        'name' => $appData['name'],
                        'platform' => $appData['platform'],
                        'icon' => $appData['icon'] ?? 'fa-cube',
                        'color' => $appData['color'] ?? 'blue',
                        'is_active' => true,
                    ]
                );

                // Migrate API key if exists and not already set
                if ($apiKey && !$app->api_key_encrypted) {
                    $app->api_key_encrypted = Crypt::encryptString($apiKey);
                    $app->api_key_prefix = substr($apiKey, 0, 12) . '...';
                    $app->api_key_generated_at = now();
                    $app->save();
                    $this->command->info("  - {$app->name} (API key migrated)");
                } else {
                    $this->command->info("  - {$app->name}");
                }
            }
        }

        $this->command->info('');
        $this->command->info('Migrated ' . Product::count() . ' products and ' . App::count() . ' apps from config.');
    }
}
