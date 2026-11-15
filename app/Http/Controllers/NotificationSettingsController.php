<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;

/** Each signed-in user chooses which notifications they receive. */
class NotificationSettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('notifications.settings', NotificationPreference::settingsFor($request->user()));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'nullable|array',
            'enabled.*' => 'string',
            'low_stock_threshold' => 'nullable|integer|min:0|max:100000',
        ]);

        // Unticked boxes are not submitted, so every option the user can see starts off.
        $enabled = array_flip($data['enabled'] ?? []);
        $toggles = collect(NotificationPreference::settingsFor($request->user())['options'])
            ->mapWithKeys(fn ($option) => [$option['key'] => isset($enabled[$option['key']])])
            ->all();

        NotificationPreference::saveFor(
            $request->user(),
            $toggles,
            isset($data['low_stock_threshold']) ? (int) $data['low_stock_threshold'] : null,
        );

        return redirect()->route('notification-settings.edit')->with('status', 'Notification settings saved.');
    }
}
