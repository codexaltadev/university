<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Faq\Models\Faq;

// Route names start with "faq."

// FR-FAQ-05 "Was this helpful?": a visible FAQ, yes / no; limited per visitor
Route::post('faq/{faq}/helpful', function (Request $request, Faq $faq) {
    abort_unless($faq->isFrontendVisible(), 404);
    $answer = $request->validate(['answer' => ['required', 'in:yes,no']])['answer'];
    $faq->newQuery()->whereKey($faq->getKey())->increment($answer === 'yes' ? 'helpful_yes' : 'helpful_no');   // not an edit: no log, no "updated"

    return response()->json(['ok' => true]);
})->middleware('throttle:30,1')->name('faq.helpful');
