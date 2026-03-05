<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppConfig;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    /**
     * Display list of all configs
     */
    public function index(Request $request)
    {
        $appFilter = $request->input('app');

        $query = AppConfig::with(['versions' => fn($q) => $q->latest('version')->limit(1)])
            ->orderBy('app_identifier')
            ->orderBy('config_key');

        if ($appFilter) {
            $query->where('app_identifier', $appFilter);
        }

        $configs = $query->get();

        // Get unique app identifiers for filter dropdown
        $apps = App::select('identifier', 'name')
            ->orderBy('name')
            ->get();

        // Group configs by app
        $groupedConfigs = $configs->groupBy('app_identifier');

        return view('admin.configs.index', [
            'groupedConfigs' => $groupedConfigs,
            'apps' => $apps,
            'appFilter' => $appFilter,
        ]);
    }

    /**
     * Show create config form
     */
    public function create()
    {
        $apps = App::select('identifier', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.configs.create', [
            'apps' => $apps,
            'contentTypes' => ['ini', 'json', 'xml', 'text'],
            'defaultTemplate' => AppConfig::DEFAULT_HEADER_TEMPLATE,
        ]);
    }

    /**
     * Store new config
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'app_identifier' => 'required|string|max:255',
            'config_key' => 'required|string|max:100|regex:/^[a-z0-9_-]+$/',
            'filename' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content_type' => 'required|in:ini,json,xml,text',
            'embed_version_header' => 'boolean',
            'version_header_template' => 'nullable|string',
            'content' => 'nullable|string',
            'change_notes' => 'nullable|string|max:500',
        ]);

        // Check for duplicate
        if (AppConfig::where('app_identifier', $validated['app_identifier'])
            ->where('config_key', $validated['config_key'])
            ->exists()) {
            return back()
                ->withInput()
                ->withErrors(['config_key' => 'A config with this key already exists for this app.']);
        }

        $config = AppConfig::create([
            'app_identifier' => $validated['app_identifier'],
            'config_key' => $validated['config_key'],
            'filename' => $validated['filename'],
            'description' => $validated['description'] ?? null,
            'content_type' => $validated['content_type'],
            'embed_version_header' => $request->boolean('embed_version_header', true),
            'version_header_template' => $validated['version_header_template'] ?? null,
        ]);

        // Create initial version if content provided
        if (!empty($validated['content'])) {
            $config->createVersion(
                $validated['content'],
                $validated['change_notes'] ?? 'Initial version',
                auth()->id()
            );
        }

        return redirect()->route('admin.configs.edit', $config)
            ->with('success', "Config '{$config->config_key}' created successfully.");
    }

    /**
     * Show edit config form
     */
    public function edit(AppConfig $config)
    {
        $config->load(['versions.creator']);

        return view('admin.configs.edit', [
            'config' => $config,
            'contentTypes' => ['ini', 'json', 'xml', 'text'],
            'defaultTemplate' => AppConfig::DEFAULT_HEADER_TEMPLATE,
        ]);
    }

    /**
     * Update config content (creates new version)
     */
    public function update(Request $request, AppConfig $config)
    {
        $validated = $request->validate([
            'filename' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content_type' => 'required|in:ini,json,xml,text',
            'embed_version_header' => 'boolean',
            'version_header_template' => 'nullable|string',
            'content' => 'required|string',
            'change_notes' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        // Update config metadata
        $config->update([
            'filename' => $validated['filename'],
            'description' => $validated['description'] ?? null,
            'content_type' => $validated['content_type'],
            'embed_version_header' => $request->boolean('embed_version_header', true),
            'version_header_template' => $validated['version_header_template'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Check if content changed
        $currentContent = $config->getCurrentContent();
        if ($currentContent !== $validated['content']) {
            $config->createVersion(
                $validated['content'],
                $validated['change_notes'] ?? null,
                auth()->id()
            );
            $message = "Config updated and new version {$config->fresh()->current_version} created.";
        } else {
            $message = "Config settings updated (content unchanged).";
        }

        return redirect()->route('admin.configs.edit', $config)
            ->with('success', $message);
    }

    /**
     * Delete config and all versions
     */
    public function destroy(AppConfig $config)
    {
        $key = $config->config_key;
        $config->delete();

        return redirect()->route('admin.configs.index')
            ->with('success', "Config '{$key}' deleted.");
    }

    /**
     * Rollback to a specific version
     */
    public function rollback(Request $request, AppConfig $config)
    {
        $validated = $request->validate([
            'version' => 'required|integer|min:1',
        ]);

        if ($config->rollbackTo($validated['version'])) {
            return redirect()->route('admin.configs.edit', $config)
                ->with('success', "Rolled back to version {$validated['version']}. New version {$config->fresh()->current_version} created.");
        }

        return back()->with('error', "Version {$validated['version']} not found.");
    }

    /**
     * View a specific version's content (AJAX)
     */
    public function viewVersion(AppConfig $config, int $version)
    {
        $versionRecord = $config->versions()->where('version', $version)->first();

        if (!$versionRecord) {
            return response()->json(['error' => 'Version not found'], 404);
        }

        return response()->json([
            'version' => $versionRecord->version,
            'content' => $versionRecord->content,
            'hash' => $versionRecord->content_hash,
            'size' => $versionRecord->formatted_size,
            'notes' => $versionRecord->change_notes,
            'createdAt' => $versionRecord->created_at->setTimezone(config('app.timezone'))->format('M j, Y g:i A'),
            'createdBy' => $versionRecord->creator?->name ?? 'System',
        ]);
    }
}
