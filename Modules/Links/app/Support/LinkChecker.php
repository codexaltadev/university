<?php

namespace Modules\Links\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Links\Models\Link;
use Modules\Links\Notifications\LinksBroken;
use Platform\Notifications\Notifications;
use Throwable;

/**
 * FR-LINK-03: asks every active outside link for an answer (HEAD, else GET; 10 seconds) and keeps
 * the status; newly broken links are reported to the people who look after them.
 */
class LinkChecker
{
    /** @return array{checked: int, broken: int, newly: int} */
    public function run(): array
    {
        $checked = 0;
        $newly = [];

        Link::query()->withoutGlobalScopes()->where('is_active', true)->whereNull('deleted_at')->orderBy('id')
            ->each(function (Link $link) use (&$checked, &$newly): void {
                if (! $link->isExternal()) {
                    return;
                }
                $wasBroken = $link->isBroken();
                $code = $this->status($link->url);
                // Bookkeeping, not an edit: no updated_at, no activity log
                DB::table($link->getTable())->where('id', $link->id)->update(['last_checked_at' => now(), 'last_status_code' => $code]);
                $checked++;
                if (($code === 0 || $code >= 400) && ! $wasBroken) {
                    $newly[] = $link->id;
                }
            });

        if ($newly !== []) {
            foreach (Link::query()->withoutGlobalScopes()->whereKey($newly)->get() as $link) {
                app(Notifications::class)->send(new LinksBroken(['title' => (string) $link->title, 'url' => $link->url, 'status' => (string) $link->fresh()->last_status_code], $link));
            }
        }

        return ['checked' => $checked, 'broken' => Link::query()->withoutGlobalScopes()->where('is_active', true)->broken()->count(), 'newly' => count($newly)];
    }

    public function status(string $url): int
    {
        try {
            $http = Http::timeout(10)->connectTimeout(5)->withHeaders(['User-Agent' => 'UWMS link check'])->withoutRedirecting();
            $response = $http->head($url);
            if (in_array($response->status(), [405, 403, 501], true)) {      // some servers refuse HEAD
                $response = $http->get($url);
            }
            if ($response->redirect()) {
                $response = Http::timeout(10)->connectTimeout(5)->withHeaders(['User-Agent' => 'UWMS link check'])->get($url);
            }

            return $response->status();
        } catch (Throwable) {
            return 0;   // no answer
        }
    }
}
