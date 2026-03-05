<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppConfig;
use App\Models\DeviceApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    /**
     * Get config manifest or specific config content
     *
     * GET /api/v1/config/{app_identifier}/{config_key?}
     */
    public function show(Request $request, string $appIdentifier, ?string $configKey = null)
    {
        // Validate API key (supports both app keys and device keys)
        $apiKey = $request->header('X-API-Key') ?? $request->header('X-Device-Key');

        if (!$this->validateApiKey($apiKey, $appIdentifier, $request)) {
            Log::warning('Vitalytics Config: Invalid API key attempt', [
                'app' => $appIdentifier,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // If no config_key, return manifest of all configs
        if (!$configKey) {
            return $this->getConfigManifest($appIdentifier);
        }

        // Get specific config
        return $this->getConfigContent($appIdentifier, $configKey);
    }

    /**
     * Get manifest of all configs for an app
     */
    protected function getConfigManifest(string $appIdentifier): JsonResponse
    {
        $configs = AppConfig::forApp($appIdentifier)->active()->get();

        $manifest = [];
        foreach ($configs as $config) {
            $manifest[$config->config_key] = [
                'version' => $config->current_version,
                'hash' => $config->getCurrentHash(),
                'filename' => $config->filename,
                'contentType' => $config->content_type,
            ];
        }

        return response()->json([
            'success' => true,
            'appIdentifier' => $appIdentifier,
            'configs' => $manifest,
        ]);
    }

    /**
     * Get specific config content
     */
    protected function getConfigContent(string $appIdentifier, string $configKey)
    {
        $config = AppConfig::forApp($appIdentifier)
            ->where('config_key', $configKey)
            ->active()
            ->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'error' => 'Config not found',
            ], 404);
        }

        if ($config->current_version === 0) {
            return response()->json([
                'success' => false,
                'error' => 'Config has no content',
            ], 404);
        }

        $content = $config->getContentWithHeader();
        $hash = $config->getCurrentHash();

        return response($content, 200)
            ->header('Content-Type', $config->getHttpContentType())
            ->header('X-Config-Version', $config->current_version)
            ->header('X-Config-Hash', $hash)
            ->header('ETag', '"' . $hash . '"');
    }

    /**
     * Validate API key (allows app keys, read keys, and device keys)
     */
    private function validateApiKey(?string $apiKey, ?string $appIdentifier, Request $request = null): bool
    {
        if (!$apiKey || !$appIdentifier) {
            return false;
        }

        // Check for device API key first (X-Device-Key header or device key format)
        $deviceKey = $request?->header('X-Device-Key') ?? $apiKey;
        $deviceApiKey = DeviceApiKey::findAndValidate($deviceKey);
        if ($deviceApiKey && $deviceApiKey->isValid() && $deviceApiKey->app_identifier === $appIdentifier) {
            // Update last used timestamp
            $deviceApiKey->touchLastUsed($request?->ip());
            return true;
        }

        // Check database for app API keys
        $app = App::findByApiKey($appIdentifier, $apiKey);
        if ($app) {
            return true;
        }

        // Fallback to config for backward compatibility
        $writeKeys = config('vitalytics.api_keys', []);
        if (isset($writeKeys[$appIdentifier]) && $writeKeys[$appIdentifier] === $apiKey) {
            return true;
        }

        // Check read keys
        $readKeys = config('vitalytics.read_keys', []);
        if (isset($readKeys[$appIdentifier]) && $readKeys[$appIdentifier] === $apiKey) {
            return true;
        }

        return false;
    }
}
