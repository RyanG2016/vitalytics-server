<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductAlertSetting;
use App\Services\AiAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateAnalyticsAnalysis extends Command
{
    protected $signature = 'analysis:analytics {--product= : Specific product slug to analyze} {--force : Run even if not scheduled hour}';
    protected $description = 'Generate AI-powered daily analytics analysis for products';

    public function handle(AiAnalysisService $analysisService): int
    {
        $currentHour = (int) Carbon::now()->format('G'); // 0-23

        // Check if specific product requested
        if ($productSlug = $this->option('product')) {
            $product = Product::where('slug', $productSlug)->first();

            if (!$product) {
                $this->error("Product not found: {$productSlug}");
                return Command::FAILURE;
            }

            $this->info("Generating analytics analysis for {$product->name}...");
            $analysis = $analysisService->generateAnalyticsAnalysis($product);

            if ($analysis) {
                $this->info("Analytics analysis generated and emailed successfully!");
                $this->line("\n" . $analysis);
            } else {
                $this->warn("No analysis generated (no analytics events or not enabled)");
            }

            return Command::SUCCESS;
        }

        // Process all products scheduled for this hour
        // Analytics runs 1 hour after health analysis
        $analysisHour = ($currentHour + 23) % 24; // -1 hour, wrapped

        $settings = ProductAlertSetting::where('ai_analysis_enabled', true)
            ->when(!$this->option('force'), function ($query) use ($analysisHour) {
                $query->where('ai_analysis_hour', $analysisHour);
            })
            ->with('product')
            ->get();

        if ($settings->isEmpty()) {
            $this->info("No products scheduled for analytics analysis at hour {$currentHour}");
            return Command::SUCCESS;
        }

        $this->info("Processing " . $settings->count() . " product(s) for analytics analysis...");

        $successCount = 0;
        $skipCount = 0;

        foreach ($settings as $setting) {
            $product = $setting->product;

            if (!$product) {
                continue;
            }

            $this->line("  Processing: {$product->name}");

            try {
                $analysis = $analysisService->generateAnalyticsAnalysis($product);

                if ($analysis) {
                    $this->info("    ✓ Analytics analysis generated and emailed");
                    $successCount++;
                } else {
                    $this->line("    - Skipped (no analytics events or disabled)");
                    $skipCount++;
                }
            } catch (\Exception $e) {
                $this->error("    ✗ Error: " . $e->getMessage());
                Log::error("GenerateAnalyticsAnalysis failed for {$product->name}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("\nCompleted: {$successCount} generated, {$skipCount} skipped");

        return Command::SUCCESS;
    }
}
