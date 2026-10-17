<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Models\Subscriber;

// Route names start with "newsletter."

Route::middleware('throttle:20,1')->prefix('newsletter')->group(function () {
    // FR-NLT-02: the link in the confirmation email (signed, 7 days)
    Route::get('confirm/{subscriber:token}', function (Subscriber $subscriber) {
        $subscriber->confirm();

        return response()->view('newsletter::message', ['message' => __('newsletter::newsletter.confirm.done')]);
    })->middleware('signed')->name('newsletter.confirm');

    // FR-NLT-03: one click unsubscribes (the 64-character token is the key); the reason is optional
    Route::get('unsubscribe/{subscriber:token}', function (Subscriber $subscriber) {
        if ($subscriber->status !== 'unsubscribed') {
            $subscriber->unsubscribe();
        }

        return response()->view('newsletter::unsubscribed', ['subscriber' => $subscriber, 'saved' => false]);
    })->name('newsletter.unsubscribe');

    Route::post('unsubscribe/{subscriber:token}', function (Request $request, Subscriber $subscriber) {
        $reason = trim((string) $request->input('reason'));
        $subscriber->forceFill(['unsubscribe_reason' => $reason !== '' ? mb_substr($reason, 0, 255) : null])->save();

        return response()->view('newsletter::unsubscribed', ['subscriber' => $subscriber, 'saved' => true]);
    })->name('newsletter.unsubscribe.reason');
});
