<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\App;

class HealthMonitorSetup extends Command
{
    protected $signature = 'vitalytics:setup
                            {--generate-keys : Generate API keys for apps without keys}';

    protected $description = 'Set up the Vitalytics database and configuration';

    public function handle(): int
    {
        $this->info('Vitalytics Setup');
        $this->line('');

        // Check database connection
        $this->info('Checking database connection...');
        try {
            DB::connection()->getPdo();
            $this->line('✓ Connected to database');
        } catch (\Exception $e) {
            $this->error('✗ Could not connect to database');
            $this->line('');
            $this->warn('Make sure you have:');
            $this->line('1. Created the database: CREATE DATABASE vitalytics;');
            $this->line('2. Updated .env with correct DB_* variables');
            $this->line('');
            $this->line('Error: ' . $e->getMessage());
            return 1;
        }

        // Run migrations
        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        // Generate API keys if requested
        if ($this->option('generate-keys')) {
            $this->line('');
            $this->info('Generating API keys for apps without keys...');
            $this->line('');

            $products = Product::with('apps')->active()->get();

            if ($products->isEmpty()) {
                $this->warn('No products found in database.');
                $this->line('Create products and apps via the admin UI first.');
            } else {
                foreach ($products as $product) {
                    $this->line("# {$product->name}");
                    foreach ($product->apps as $app) {
                        if (!$app->api_key_encrypted) {
                            $plainKey = $app->generateApiKey();
                            $this->line("  {$app->identifier}: {$plainKey}");
                            $this->warn("  ^ Save this key! It won't be shown again.");
                        } else {
                            $this->line("  {$app->identifier}: (already has key: {$app->api_key_prefix})");
                        }
                    }
                    $this->line('');
                }
            }
        }

        $this->line('');
        $this->info('Setup complete!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('1. Create products and apps via Admin > Products');
        $this->line('2. Generate API keys for each app');
        $this->line('3. Configure your client SDKs with the API keys');

        return 0;
    }
}
