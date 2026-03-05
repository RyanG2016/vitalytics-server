<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\App;
use App\Models\AppSecret;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display products list with their apps
     */
    public function index()
    {
        $products = Product::with(['apps' => fn($q) => $q->orderBy('platform')])
            ->ordered()
            ->get();

        return view('admin.products.index', [
            'products' => $products,
        ]);
    }

    /**
     * Update product display order (AJAX)
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:products,id',
        ]);

        foreach ($validated['order'] as $position => $productId) {
            Product::where('id', $productId)->update(['display_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Show create product form
     */
    public function create()
    {
        return view('admin.products.create', [
            'platforms' => App::PLATFORMS,
        ]);
    }

    /**
     * Store new product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|unique:products,slug|regex:/^[a-z0-9-]+$/',
            'description' => 'nullable|string|max:1000',
            'icon' => 'required|string|max:50',
            'color' => 'required|string|max:20|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Product::generateSlug($validated['name']);
            
            // Ensure slug is unique
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Product::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter++;
            }
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $product = Product::create($validated);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "Product '{$product->name}' created successfully. Now add apps below.");
    }

    /**
     * Show edit product form with apps
     */
    public function edit(Product $product)
    {
        $product->load(['apps' => fn($q) => $q->with(['linkedApp', 'linkedFrom'])->orderBy('platform')]);

        return view('admin.products.edit', [
            'product' => $product,
            'platforms' => App::PLATFORMS,
        ]);
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('products')->ignore($product->id)],
            'description' => 'nullable|string|max:1000',
            'icon' => 'required|string|max:50',
            'color' => 'required|string|max:20|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', "Product '{$product->name}' updated successfully.");
    }

    /**
     * Delete product (and all apps)
     */
    public function destroy(Product $product)
    {
        $name = $product->name;
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "Product '{$name}' and all its apps deleted.");
    }

    // --- App Management (nested under product) ---

    /**
     * Show create app form
     */
    public function createApp(Product $product)
    {
        // Get platforms already used by this product (for warning display)
        $usedPlatforms = $product->apps->pluck('platform')->toArray();

        // Get existing identifiers for this product (for suffix suggestions)
        $usedIdentifiers = $product->apps->pluck('identifier')->toArray();

        return view('admin.products.apps.create', [
            'product' => $product,
            'platforms' => App::PLATFORMS,
            'usedPlatforms' => $usedPlatforms,
            'usedIdentifiers' => $usedIdentifiers,
        ]);
    }

    /**
     * Store new app
     */
    public function storeApp(Request $request, Product $product)
    {
        $validated = $request->validate([
            'platform' => ['required', Rule::in(array_keys(App::PLATFORMS))],
            'suffix' => 'nullable|string|max:50|regex:/^[a-z0-9-]+$/',
            'name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:20',
        ]);

        // Generate identifier with optional custom suffix
        $customSuffix = !empty($validated['suffix']) ? $validated['suffix'] : null;
        $identifier = App::generateIdentifier($product->slug, $validated['platform'], $customSuffix);

        // Check if identifier already exists
        if (App::where('identifier', $identifier)->exists()) {
            return back()->withErrors(['suffix' => "The identifier '{$identifier}' already exists. Please use a different suffix."])->withInput();
        }

        // Auto-generate name if not provided
        if (empty($validated['name'])) {
            $platformName = App::PLATFORMS[$validated['platform']]['name'];
            $validated['name'] = $product->name . ' ' . $platformName;
        }

        // Use platform icon if not provided
        if (empty($validated['icon'])) {
            $validated['icon'] = App::PLATFORMS[$validated['platform']]['icon'];
        }

        $app = $product->apps()->create([
            'identifier' => $identifier,
            'name' => $validated['name'],
            'platform' => $validated['platform'],
            'icon' => $validated['icon'],
            'color' => $validated['color'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Generate API key if requested (default: yes)
        $plainKey = null;
        if ($request->boolean('generate_key', true)) {
            $plainKey = $app->generateApiKey();
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "App '{$app->name}' created successfully.")
            ->with('new_api_key', $plainKey)
            ->with('new_api_key_app', $app->identifier);
    }

    /**
     * Show edit app form
     */
    public function editApp(Product $product, App $app)
    {
        return view('admin.products.apps.edit', [
            'product' => $product,
            'app' => $app,
            'platforms' => App::PLATFORMS,
        ]);
    }

    /**
     * Update app
     */
    public function updateApp(Request $request, Product $product, App $app)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:20',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $app->update($validated);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "App '{$app->name}' updated successfully.");
    }

    /**
     * Delete app
     */
    public function destroyApp(Product $product, App $app)
    {
        $name = $app->name;
        $app->delete();

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "App '{$name}' deleted.");
    }

    /**
     * Regenerate API key for an app
     */
    public function regenerateApiKey(Product $product, App $app)
    {
        $plainKey = $app->generateApiKey();

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "API key regenerated for '{$app->name}'.")
            ->with('new_api_key', $plainKey)
            ->with('new_api_key_app', $app->identifier);
    }

    /**
     * Show decrypted API key for an app (AJAX)
     */
    public function showApiKey(Product $product, App $app)
    {
        $apiKey = $app->decryptedApiKey();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'No API key set for this app',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'apiKey' => $apiKey,
            'identifier' => $app->identifier,
        ]);
    }

    /**
     * Generate or rotate secret for an app
     */
    public function generateSecret(Request $request, Product $product, App $app)
    {
        $gracePeriod = $request->input('grace_period', 30);

        // Check if there are existing active secrets
        $existingSecrets = AppSecret::findActiveForApp($app->identifier);

        if ($existingSecrets->isEmpty()) {
            // First secret - just generate
            $result = AppSecret::generateForApp($app->identifier);
        } else {
            // Rotate - set expiry on existing
            $result = AppSecret::rotateForApp($app->identifier, $gracePeriod);
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'New secret generated successfully!')
            ->with('new_secret', $result['plainSecret'])
            ->with('new_secret_app', $app->identifier);
    }

    /**
     * Link an app's API key to another app in the same product
     */
    public function linkApp(Request $request, Product $product, App $app)
    {
        $validated = $request->validate([
            'linked_app_id' => ['required', 'exists:apps,id'],
        ]);

        $linkedApp = App::findOrFail($validated['linked_app_id']);

        // Ensure both apps belong to the same product
        if ($linkedApp->product_id !== $product->id) {
            return back()->withErrors(['linked_app_id' => 'Can only link to apps within the same product.']);
        }

        // Prevent self-linking
        if ($linkedApp->id === $app->id) {
            return back()->withErrors(['linked_app_id' => 'Cannot link an app to itself.']);
        }

        // Prevent circular linking (don't link to an app that's already linked to this one)
        if ($linkedApp->linked_app_id === $app->id) {
            return back()->withErrors(['linked_app_id' => 'Cannot create circular link.']);
        }

        $app->update(['linked_app_id' => $linkedApp->id]);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "'{$app->name}' now shares API key with '{$linkedApp->name}'.");
    }

    /**
     * Remove API key link from an app
     */
    public function unlinkApp(Product $product, App $app)
    {
        $linkedAppName = $app->linkedApp?->name ?? 'unknown';
        $app->update(['linked_app_id' => null]);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "'{$app->name}' no longer shares API key with '{$linkedAppName}'.");
    }
}
