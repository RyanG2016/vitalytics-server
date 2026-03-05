<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Store new feedback (AJAX)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:feedback,feature,enhancement,bug',
            'message' => 'required|string|min:10|max:5000',
        ]);

        $feedback = Feedback::create([
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'message' => $validated['message'],
            'page_url' => $request->header('Referer'),
        ]);

        return response()->json([
            'success' => true,
            'feedback_id' => $feedback->feedback_id,
            'message' => 'Thank you for your feedback!',
        ]);
    }
}
