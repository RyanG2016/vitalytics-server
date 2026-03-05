<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductIcon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductIconController extends Controller
{
    /**
     * Display product icons management page
     */
    public function index()
    {
        $products = Product::active()->get();
        $customIcons = ProductIcon::all()->keyBy('product_id');

        // Merge product config with custom icons
        $productList = [];
        foreach ($products as $product) {
            $productId = $product->slug;
            $customIcon = $customIcons->get($productId);
            $productList[$productId] = [
                'name' => $product->name,
                'description' => $product->description ?? '',
                'default_icon' => $product->icon ?? 'fa-cube',
                'default_color' => $product->color ?? '#666',
                'custom_icon' => $customIcon?->icon,
                'custom_icon_path' => $customIcon?->icon_path,
                'custom_icon_url' => $customIcon?->icon_url,
                'custom_color' => $customIcon?->color,
                'has_custom' => $customIcon?->hasCustomIcon() ?? false,
            ];
        }

        return view('admin.icons.index', [
            'products' => $productList,
        ]);
    }

    /**
     * Update product icon
     */
    public function update(Request $request, string $productId)
    {
        $product = Product::where('slug', $productId)->active()->first();

        if (!$product) {
            return back()->with('error', 'Invalid product');
        }

        $request->validate([
            'icon_type' => 'required|in:default,upload,url',
            'icon_file' => 'nullable|image|max:2048',
            'icon_url' => 'nullable|url|max:500',
            'color' => 'nullable|string|max:20',
        ]);

        $productIcon = ProductIcon::firstOrCreate(
            ['product_id' => $productId],
            []
        );

        $iconType = $request->input('icon_type');

        if ($iconType === 'default') {
            // Remove custom icon and use default
            if ($productIcon->icon_path) {
                Storage::disk('public')->delete($productIcon->icon_path);
            }
            $productIcon->delete();
            return back()->with('success', 'Reset to default icon');
        }

        if ($iconType === 'upload' && $request->hasFile('icon_file')) {
            // Delete old icon if exists
            if ($productIcon->icon_path) {
                Storage::disk('public')->delete($productIcon->icon_path);
            }

            $path = $request->file('icon_file')->store('product-icons', 'public');
            $productIcon->icon_path = $path;
            $productIcon->icon_url = null;
        }

        if ($iconType === 'url' && $request->filled('icon_url')) {
            // Clear uploaded file if switching to URL
            if ($productIcon->icon_path) {
                Storage::disk('public')->delete($productIcon->icon_path);
                $productIcon->icon_path = null;
            }
            $productIcon->icon_url = $request->input('icon_url');
        }

        if ($request->filled('color')) {
            $productIcon->color = $request->input('color');
        }

        $productIcon->save();

        return back()->with('success', 'Product icon updated successfully');
    }

    /**
     * Remove custom icon for a product
     */
    public function destroy(string $productId)
    {
        $productIcon = ProductIcon::where('product_id', $productId)->first();

        if ($productIcon) {
            if ($productIcon->icon_path) {
                Storage::disk('public')->delete($productIcon->icon_path);
            }
            $productIcon->delete();
        }

        return back()->with('success', 'Custom icon removed');
    }
}
