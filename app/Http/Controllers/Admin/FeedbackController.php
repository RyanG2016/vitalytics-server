<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display a listing of all feedback
     */
    public function index(Request $request)
    {
        $query = Feedback::with('user:id,name,email')->latest();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $feedback = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Feedback::count(),
            'new' => Feedback::where('status', 'new')->count(),
            'in_progress' => Feedback::where('status', 'in_progress')->count(),
            'bugs' => Feedback::where('type', 'bug')->count(),
            'features' => Feedback::where('type', 'feature')->count(),
        ];

        return view('admin.feedback.index', [
            'feedback' => $feedback,
            'stats' => $stats,
            'filters' => [
                'type' => $request->type,
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * Update feedback status and notes (AJAX)
     */
    public function update(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:new,reviewed,in_progress,completed,declined',
            'admin_notes' => 'sometimes|nullable|string|max:2000',
        ]);

        $feedback->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Feedback updated successfully',
        ]);
    }

    /**
     * Delete feedback
     */
    public function destroy(Feedback $feedback)
    {
        $feedback->delete();

        return redirect()->route('admin.feedback.index')
            ->with('success', 'Feedback deleted successfully');
    }
}
