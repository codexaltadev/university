<?php

namespace Modules\Newsletter\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Modules\Newsletter\Models\Campaign;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;

/**
 * /newsletter (FR-NLT-01): the sign-up page with the past issues (Phase 4.7g); /newsletter/issues/{id}: one past issue.
 * An issue is a sent campaign in the page's language whose lists are all public — a campaign to a private list (staff …)
 * never shows. On the main site only.
 */
class NewsletterWebsite
{
    public const PATH = 'newsletter';

    public const ISSUES = 'newsletter/issues';

    public const PER_PAGE = 9;

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        $rest = trim($rest, '/');
        if ($context->isMicrosite()) {
            return null;
        }

        if ($rest === self::PATH) {
            app(Seo::class)->set(['title' => __('newsletter::newsletter.title'), 'path' => '/'.self::PATH]);

            return response()->view('newsletter::index', [
                'intro' => settings('newsletter.intro'),
                'issues' => settings('newsletter.show_issues', true)
                    ? static::issues($locale)->paginate(self::PER_PAGE)->withPath(url('/'.self::PATH))
                    : null,
            ]);
        }

        if (preg_match('#^'.self::ISSUES.'/(\d+)$#', $rest, $m) && ($issue = static::issues($locale)->find((int) $m[1]))) {
            app(Seo::class)->set(['title' => (string) $issue->subject, 'path' => '/'.self::ISSUES.'/'.$issue->id]);

            return response()->view('newsletter::issue', ['issue' => $issue]);
        }

        return null;
    }

    /** Sent campaigns of the language whose lists are all public, the newest first */
    public static function issues(string $locale): Builder
    {
        return Campaign::query()->where('is_active', true)->where('send_status', 'sent')->whereNotNull('sent_at')->where('locale', $locale)
            ->whereHas('lists')->whereDoesntHave('lists', fn (Builder $l) => $l->where('is_public', false))
            ->latest('sent_at');
    }
}
