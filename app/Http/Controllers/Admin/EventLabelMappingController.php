<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventLabelMapping;
use App\Models\ProductIcon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventLabelMappingController extends Controller
{
    /**
     * Display the label mapping management page
     */
    public function index(Request $request)
    {
        $selectedApp = $request->input('app');
        $selectedType = $request->input('type', 'screen');
        $showTest = session('show_test', false);

        $apps = $this->getApps();
        $products = $this->getProducts();

        // If no app selected, show selection page
        if (!$selectedApp || !isset($apps[$selectedApp])) {
            return view('admin.analytics.label-mappings', [
                'apps' => $apps,
                'products' => $products,
                'selectedApp' => null,
                'selectedType' => $selectedType,
                'mappings' => [],
                'unmappedValues' => [],
                'types' => EventLabelMapping::TYPES,
            ]);
        }

        // Get existing mappings for this app and type
        $mappings = EventLabelMapping::where('app_identifier', $selectedApp)
            ->where('mapping_type', $selectedType)
            ->orderBy('raw_value')
            ->get()
            ->keyBy('raw_value');

        // Get unique values from analytics_events
        $rawValues = $this->getUniqueValues($selectedApp, $selectedType, $showTest);

        // Separate into mapped and unmapped
        $unmappedValues = [];
        foreach ($rawValues as $value) {
            if (!$mappings->has($value['raw_value'])) {
                $unmappedValues[] = $value;
            }
        }

        // Get client suggested labels from properties
        $clientLabels = $this->getClientSuggestedLabels($selectedApp, $selectedType, $showTest);

        return view('admin.analytics.label-mappings', [
            'apps' => $apps,
            'products' => $products,
            'selectedApp' => $selectedApp,
            'selectedType' => $selectedType,
            'mappings' => $mappings,
            'unmappedValues' => $unmappedValues,
            'clientLabels' => $clientLabels,
            'types' => EventLabelMapping::TYPES,
        ]);
    }

    /**
     * Store or update a mapping
     */
    public function store(Request $request)
    {
        $request->validate([
            'app_identifier' => 'required|string|max:100',
            'mapping_type' => 'required|in:' . implode(',', EventLabelMapping::TYPES),
            'raw_value' => 'required|string|max:255',
            'friendly_label' => 'required|string|max:255',
            'client_suggested_label' => 'nullable|string|max:255',
        ]);

        EventLabelMapping::updateOrCreate(
            [
                'app_identifier' => $request->input('app_identifier'),
                'mapping_type' => $request->input('mapping_type'),
                'raw_value' => $request->input('raw_value'),
            ],
            [
                'friendly_label' => $request->input('friendly_label'),
                'client_suggested_label' => $request->input('client_suggested_label'),
            ]
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Mapping saved']);
        }

        return back()->with('success', 'Label mapping saved successfully');
    }

    /**
     * Delete a mapping
     */
    public function destroy(Request $request, int $id)
    {
        $mapping = EventLabelMapping::findOrFail($id);
        $mapping->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Mapping deleted']);
        }

        return back()->with('success', 'Label mapping deleted');
    }

    /**
     * Get unique values for a mapping type from analytics_events
     */
    private function getUniqueValues(string $appIdentifier, string $type, bool $showTest = false): array
    {
        switch ($type) {
            case 'screen':
                return DB::table('analytics_events')
                    ->where('app_identifier', $appIdentifier)
                    ->where('is_test', $showTest)
                    ->whereNotNull('screen_name')
                    ->select('screen_name as raw_value')
                    ->selectRaw('COUNT(*) as usage_count')
                    ->selectRaw('MAX(event_timestamp) as last_used')
                    ->groupBy('screen_name')
                    ->orderByDesc('usage_count')
                    ->get()
                    ->map(fn($row) => [
                        'raw_value' => $row->raw_value,
                        'usage_count' => $row->usage_count,
                        'last_used' => $row->last_used,
                    ])
                    ->toArray();

            case 'element':
                return DB::table('analytics_events')
                    ->where('app_identifier', $appIdentifier)
                    ->where('is_test', $showTest)
                    ->whereNotNull('element_id')
                    ->select('element_id as raw_value')
                    ->selectRaw('COUNT(*) as usage_count')
                    ->selectRaw('MAX(event_timestamp) as last_used')
                    ->groupBy('element_id')
                    ->orderByDesc('usage_count')
                    ->get()
                    ->map(fn($row) => [
                        'raw_value' => $row->raw_value,
                        'usage_count' => $row->usage_count,
                        'last_used' => $row->last_used,
                    ])
                    ->toArray();

            case 'feature':
                // Features are stored in properties->feature
                return DB::table('analytics_events')
                    ->where('app_identifier', $appIdentifier)
                    ->where('is_test', $showTest)
                    ->where('event_category', 'feature')
                    ->whereNotNull('properties')
                    ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.feature')) as raw_value")
                    ->selectRaw('COUNT(*) as usage_count')
                    ->selectRaw('MAX(event_timestamp) as last_used')
                    ->groupByRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.feature'))")
                    ->havingRaw('raw_value IS NOT NULL')
                    ->orderByDesc('usage_count')
                    ->get()
                    ->map(fn($row) => [
                        'raw_value' => $row->raw_value,
                        'usage_count' => $row->usage_count,
                        'last_used' => $row->last_used,
                    ])
                    ->toArray();

            case 'form':
                // Forms are stored in properties->form
                return DB::table('analytics_events')
                    ->where('app_identifier', $appIdentifier)
                    ->where('is_test', $showTest)
                    ->where('event_category', 'form')
                    ->whereNotNull('properties')
                    ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.form')) as raw_value")
                    ->selectRaw('COUNT(*) as usage_count')
                    ->selectRaw('MAX(event_timestamp) as last_used')
                    ->groupByRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.form'))")
                    ->havingRaw('raw_value IS NOT NULL')
                    ->orderByDesc('usage_count')
                    ->get()
                    ->map(fn($row) => [
                        'raw_value' => $row->raw_value,
                        'usage_count' => $row->usage_count,
                        'last_used' => $row->last_used,
                    ])
                    ->toArray();

            case 'event_type':
                return DB::table('analytics_events')
                    ->where('app_identifier', $appIdentifier)
                    ->where('is_test', $showTest)
                    ->select('event_name as raw_value')
                    ->selectRaw('COUNT(*) as usage_count')
                    ->selectRaw('MAX(event_timestamp) as last_used')
                    ->groupBy('event_name')
                    ->orderByDesc('usage_count')
                    ->get()
                    ->map(fn($row) => [
                        'raw_value' => $row->raw_value,
                        'usage_count' => $row->usage_count,
                        'last_used' => $row->last_used,
                    ])
                    ->toArray();

            default:
                return [];
        }
    }

    /**
     * Get client-suggested labels from properties (label, screen_label)
     */
    private function getClientSuggestedLabels(string $appIdentifier, string $type, bool $showTest = false): array
    {
        $labels = [];

        switch ($type) {
            case 'screen':
                // Look for screen_label in properties
                $results = DB::table('analytics_events')
                    ->where('app_identifier', $appIdentifier)
                    ->where('is_test', $showTest)
                    ->whereNotNull('screen_name')
                    ->whereNotNull('properties')
                    ->whereRaw("JSON_EXTRACT(properties, '$.screen_label') IS NOT NULL")
                    ->selectRaw("screen_name as raw_value, JSON_UNQUOTE(JSON_EXTRACT(properties, '$.screen_label')) as suggested_label")
                    ->distinct()
                    ->get();

                foreach ($results as $row) {
                    if ($row->suggested_label) {
                        $labels[$row->raw_value] = $row->suggested_label;
                    }
                }
                break;

            case 'element':
                // Look for label in properties for button clicks
                $results = DB::table('analytics_events')
                    ->where('app_identifier', $appIdentifier)
                    ->where('is_test', $showTest)
                    ->whereNotNull('element_id')
                    ->whereNotNull('properties')
                    ->whereRaw("JSON_EXTRACT(properties, '$.label') IS NOT NULL")
                    ->selectRaw("element_id as raw_value, JSON_UNQUOTE(JSON_EXTRACT(properties, '$.label')) as suggested_label")
                    ->distinct()
                    ->get();

                foreach ($results as $row) {
                    if ($row->suggested_label) {
                        $labels[$row->raw_value] = $row->suggested_label;
                    }
                }
                break;

            case 'feature':
                // Look for label in properties for features
                $results = DB::table('analytics_events')
                    ->where('app_identifier', $appIdentifier)
                    ->where('is_test', $showTest)
                    ->where('event_category', 'feature')
                    ->whereNotNull('properties')
                    ->whereRaw("JSON_EXTRACT(properties, '$.feature') IS NOT NULL")
                    ->whereRaw("JSON_EXTRACT(properties, '$.label') IS NOT NULL")
                    ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.feature')) as raw_value, JSON_UNQUOTE(JSON_EXTRACT(properties, '$.label')) as suggested_label")
                    ->distinct()
                    ->get();

                foreach ($results as $row) {
                    if ($row->suggested_label) {
                        $labels[$row->raw_value] = $row->suggested_label;
                    }
                }
                break;

            case 'form':
                // Look for label in properties for forms
                $results = DB::table('analytics_events')
                    ->where('app_identifier', $appIdentifier)
                    ->where('is_test', $showTest)
                    ->where('event_category', 'form')
                    ->whereNotNull('properties')
                    ->whereRaw("JSON_EXTRACT(properties, '$.form') IS NOT NULL")
                    ->whereRaw("JSON_EXTRACT(properties, '$.label') IS NOT NULL")
                    ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.form')) as raw_value, JSON_UNQUOTE(JSON_EXTRACT(properties, '$.label')) as suggested_label")
                    ->distinct()
                    ->get();

                foreach ($results as $row) {
                    if ($row->suggested_label) {
                        $labels[$row->raw_value] = $row->suggested_label;
                    }
                }
                break;
        }

        return $labels;
    }

    /**
     * Get all parent products from database, filtered by user permissions
     */
    private function getProducts(): array
    {
        $products = Product::with('apps')->active()->get();
        $user = auth()->user();
        $accessibleProducts = $user->accessibleProducts();
        $customIcons = ProductIcon::getIconsMap();
        $result = [];

        foreach ($products as $product) {
            $productId = $product->slug;

            if (!$user->isAdmin() && !in_array($productId, $accessibleProducts)) {
                continue;
            }

            $custom = $customIcons[$productId] ?? null;

            $result[$productId] = [
                'name' => $product->name,
                'color' => $custom['color'] ?? $product->color ?? '#666',
                'icon' => $product->icon ?? 'fa-cube',
                'custom_icon' => $custom['icon'] ?? null,
                'has_custom_icon' => $custom['has_custom'] ?? false,
            ];
        }

        return $result;
    }

    /**
     * Get all configured apps from database, filtered by user permissions
     */
    private function getApps(): array
    {
        $apps = [];
        $products = Product::with('apps')->active()->get();
        $user = auth()->user();
        $accessibleProducts = $user->accessibleProducts();

        foreach ($products as $product) {
            $productId = $product->slug;

            if (!$user->isAdmin() && !in_array($productId, $accessibleProducts)) {
                continue;
            }

            foreach ($product->apps as $app) {
                $apps[$app->identifier] = [
                    'name' => $app->name,
                    'icon' => $app->platform_icon,
                    'product' => $productId,
                    'product_name' => $product->name,
                ];
            }
        }

        return $apps;
    }
}
