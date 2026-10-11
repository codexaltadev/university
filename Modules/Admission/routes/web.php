<?php

use Illuminate\Support\Facades\Route;
use Modules\Admission\Models\AdmissionSubscriber;

// Route names start with "admission."

// FR-ADM-09: the links in the confirmation email (signed — nobody else can confirm or unsubscribe)
Route::middleware(['signed', 'throttle:20,1'])->prefix('admission/subscription')->group(function () {
    Route::get('{subscriber}/confirm', function (AdmissionSubscriber $subscriber) {
        $subscriber->verified_at ??= now();
        $subscriber->save();

        return response()->view('admission::subscription', ['message' => __('admission::admission.subscribe.confirmed')]);
    })->name('admission.subscriber.verify');

    Route::get('{subscriber}/unsubscribe', function (AdmissionSubscriber $subscriber) {
        $subscriber->update(['unsubscribed_at' => now(), 'unsubscribe_reason' => __('admission::admission.by_link')]);

        return response()->view('admission::subscription', ['message' => __('admission::admission.subscribe.unsubscribed')]);
    })->name('admission.subscriber.unsubscribe');
});
