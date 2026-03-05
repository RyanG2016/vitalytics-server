<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class HelpController extends Controller
{
    /**
     * Display the integrations help page (Health Monitoring)
     */
    public function integrations(Request $request)
    {
        $tab = $request->input('tab', 'overview');

        return view('help.integrations', [
            'tab' => $tab,
        ]);
    }

    /**
     * Display the analytics integration help page
     */
    public function analytics(Request $request)
    {
        $tab = $request->input('tab', 'overview');

        return view('help.analytics', [
            'tab' => $tab,
        ]);
    }

    /**
     * Display the unified SDK documentation page (public, no auth)
     */
    public function sdk(Request $request)
    {
        $tab = $request->input('tab', 'swift');

        return view('docs.sdk', [
            'tab' => $tab,
        ]);
    }

    /**
     * Serve the Analytics SDK documentation markdown file (public, no auth)
     */
    public function analyticsSdkDocs()
    {
        $filePath = base_path('docs/ANALYTICS_SDK_INTEGRATION.md');

        if (!File::exists($filePath)) {
            abort(404, 'Documentation file not found');
        }

        $content = File::get($filePath);

        return response($content, 200)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Content-Disposition', 'inline; filename="ANALYTICS_SDK_INTEGRATION.md"');
    }
}
