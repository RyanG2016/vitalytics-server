<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductFeedback;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProductFeedbackController extends Controller
{
    /**
     * Display feedback list
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $accessibleProducts = $user->accessibleProducts();

        // Get filter parameters
        $product = $request->input('product');
        $app = $request->input('app');
        $category = $request->input('category');
        $rating = $request->input('rating');
        $showTest = $request->boolean('show_test', session('show_test', false));
        $unreadOnly = $request->boolean('unread_only', false);

        // Build query
        $query = ProductFeedback::query()
            ->orderBy('created_at', 'desc');

        // Filter by test mode
        if (!$showTest) {
            $query->where('is_test', false);
        }

        // Filter by product access
        if (!$user->isAdmin()) {
            $appIdentifiers = Product::whereIn('slug', $accessibleProducts)
                ->with('apps')
                ->get()
                ->flatMap(fn($p) => $p->apps->pluck('identifier'))
                ->toArray();
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        // Filter by specific app (sub-product)
        if ($app) {
            $query->where('app_identifier', $app);
        }
        // Filter by product (all apps under that product)
        elseif ($product) {
            $productModel = Product::where('slug', $product)->with('apps')->first();
            if ($productModel) {
                $appIdentifiers = $productModel->apps->pluck('identifier')->toArray();
                $query->whereIn('app_identifier', $appIdentifiers);
            }
        }

        // Filter by category
        if ($category) {
            $query->where('category', $category);
        }

        // Filter by rating
        if ($rating) {
            $query->where('rating', $rating);
        }

        // Filter unread only
        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        // Paginate with app relationship for better display
        $feedback = $query->with(['app.product'])->paginate(25)->withQueryString();

        // Get products for filter
        $productsWithApps = Product::active()->ordered()->with('apps')
            ->get()
            ->filter(fn($p) => $user->isAdmin() || in_array($p->slug, $accessibleProducts));

        $products = $productsWithApps->mapWithKeys(fn($p) => [$p->slug => $p->name]);

        // Get apps for filter (grouped by product for the dropdown)
        $apps = $productsWithApps->flatMap(function ($p) {
            return $p->apps->map(fn($a) => [
                'identifier' => $a->identifier,
                'name' => $a->name,
                'product_name' => $p->name,
                'product_slug' => $p->slug,
            ]);
        })->sortBy('product_name')->values();

        // Get stats
        $stats = $this->getStats($showTest, $user->isAdmin() ? null : $accessibleProducts);

        return view('admin.analytics.feedback', [
            'feedback' => $feedback,
            'products' => $products,
            'apps' => $apps,
            'stats' => $stats,
            'filters' => [
                'product' => $product,
                'app' => $app,
                'category' => $category,
                'rating' => $rating,
                'show_test' => $showTest,
                'unread_only' => $unreadOnly,
            ],
            'categories' => ProductFeedback::CATEGORIES,
        ]);
    }

    /**
     * Mark feedback as read
     */
    public function markRead(Request $request, ProductFeedback $feedback)
    {
        $feedback->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Feedback marked as read');
    }

    /**
     * Mark feedback as unread
     */
    public function markUnread(Request $request, ProductFeedback $feedback)
    {
        $feedback->markAsUnread();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Feedback marked as unread');
    }

    /**
     * Mark all feedback as read (respects current filters)
     */
    public function markAllRead(Request $request)
    {
        $user = auth()->user();
        $accessibleProducts = $user->accessibleProducts();

        // Get filter parameters
        $product = $request->input('product');
        $app = $request->input('app');
        $category = $request->input('category');
        $rating = $request->input('rating');
        $showTest = $request->boolean('show_test', session('show_test', false));

        // Build query
        $query = ProductFeedback::query()->where('is_read', false);

        // Filter by test mode
        if (!$showTest) {
            $query->where('is_test', false);
        }

        // Filter by product access
        if (!$user->isAdmin()) {
            $appIdentifiers = Product::whereIn('slug', $accessibleProducts)
                ->with('apps')
                ->get()
                ->flatMap(fn($p) => $p->apps->pluck('identifier'))
                ->toArray();
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        // Filter by specific app (sub-product)
        if ($app) {
            $query->where('app_identifier', $app);
        }
        // Filter by product (all apps under that product)
        elseif ($product) {
            $productModel = Product::where('slug', $product)->with('apps')->first();
            if ($productModel) {
                $appIdentifiers = $productModel->apps->pluck('identifier')->toArray();
                $query->whereIn('app_identifier', $appIdentifiers);
            }
        }

        // Filter by category
        if ($category) {
            $query->where('category', $category);
        }

        // Filter by rating
        if ($rating) {
            $query->where('rating', $rating);
        }

        $count = $query->count();
        $query->update(['is_read' => true]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'count' => $count]);
        }

        return back()->with('success', "Marked {$count} feedback item(s) as read");
    }

    /**
     * Delete feedback
     */
    public function destroy(Request $request, ProductFeedback $feedback)
    {
        $feedback->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Feedback deleted');
    }

    /**
     * Get feedback stats
     */
    private function getStats(bool $showTest, ?array $accessibleProducts = null): array
    {
        $query = ProductFeedback::query();

        if (!$showTest) {
            $query->where('is_test', false);
        }

        if ($accessibleProducts) {
            $appIdentifiers = Product::whereIn('slug', $accessibleProducts)
                ->with('apps')
                ->get()
                ->flatMap(fn($p) => $p->apps->pluck('identifier'))
                ->toArray();
            $query->whereIn('app_identifier', $appIdentifiers);
        }

        $total = (clone $query)->count();
        $unread = (clone $query)->where('is_read', false)->count();
        $today = (clone $query)->whereDate('created_at', today())->count();
        $thisWeek = (clone $query)->where('created_at', '>=', now()->startOfWeek())->count();

        // Category breakdown
        $byCategory = (clone $query)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // Average rating
        $avgRating = (clone $query)->whereNotNull('rating')->avg('rating');

        return [
            'total' => $total,
            'unread' => $unread,
            'today' => $today,
            'thisWeek' => $thisWeek,
            'byCategory' => $byCategory,
            'avgRating' => $avgRating ? round($avgRating, 1) : null,
        ];
    }

    /**
     * Get feedback counts for products (used by dashboard)
     */
    public static function getFeedbackCounts(bool $showTest = false): array
    {
        $query = ProductFeedback::query()
            ->selectRaw('app_identifier, COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread')
            ->groupBy('app_identifier');

        if (!$showTest) {
            $query->where('is_test', false);
        }

        return $query->get()
            ->mapWithKeys(fn($row) => [
                $row->app_identifier => [
                    'total' => $row->total,
                    'unread' => $row->unread,
                ]
            ])
            ->toArray();
    }
}
