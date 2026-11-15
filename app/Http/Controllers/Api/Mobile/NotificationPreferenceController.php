<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Lets any signed-in user (customer or staff) choose which notifications they get. */
class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => NotificationPreference::settingsFor($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'boolean',
            'low_stock_threshold' => 'nullable|integer|min:0|max:100000',
        ]);

        NotificationPreference::saveFor(
            $request->user(),
            $data['preferences'],
            isset($data['low_stock_threshold']) ? (int) $data['low_stock_threshold'] : null,
        );

        return response()->json([
            'message' => 'Notification settings saved.',
            'data' => NotificationPreference::settingsFor($request->user()),
        ]);
    }
}
