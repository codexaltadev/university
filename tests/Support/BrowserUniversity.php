<?php

namespace Tests\Support;

use Modules\Admission\Models\Circular;
use Modules\Department\Models\Department;
use Modules\Events\Models\Event;
use Modules\Faculty\Models\Faculty;
use Modules\Faq\Models\Faq;
use Modules\News\Models\Post;
use Modules\Notice\Models\Notice;
use Modules\Program\Database\Factories\ProgramFactory;
use Modules\Program\Models\Program;
use Modules\Scholarship\Models\Scholarship;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Modules\Tuition\Models\TuitionLine;
use Modules\Waiver\Models\Board;
use Modules\Waiver\Models\WaiverCategory;
use Modules\Waiver\Models\WaiverRule;
use Platform\Registry\Registry;
use Platform\Relations\RelationEngine;

/**
 * A small university for the browser tests (Test Plan §4 TestUniversitySeeder, trimmed):
 * one faculty, department and program with its tuition and a result waiver, and one
 * of each record the most visited pages list (notice, news, event, FAQ, scholarship, circular).
 */
final class BrowserUniversity
{
    /**
     * Elements sticking out past the right edge (none = []). The body clips sideways overflow, so each element is
     * measured itself: content already slid in (.reveal opened), skipping what a scroller / slider inside clips.
     */
    public const OVERFLOW_JS = <<<'JS'
        () => {
            if (! document.getElementById('measure-revealed')) {
                const style = Object.assign(document.createElement('style'), { id: 'measure-revealed', textContent: '.reveal { transition: none !important; }' });
                document.head.append(style);
                document.querySelectorAll('.reveal').forEach((el) => el.classList.add('is-visible'));
            }
            const width = document.documentElement.clientWidth;
            const clipped = (el) => {
                for (let p = el.parentElement; p && p !== document.body; p = p.parentElement) {
                    if (getComputedStyle(p).overflowX !== 'visible') return true;
                }
                return false;
            };
            return [...document.querySelectorAll('body *')]
                .filter((el) => { const r = el.getBoundingClientRect(); return r.width > 0 && r.right > width + 1 && ! clipped(el); })
                .slice(0, 5).map((el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + [...el.classList].slice(0, 4).map((c) => '.' + c).join(''));
        }
        JS;

    /** Turns the page's print rules on for the screen (Playwright has no print media here) and fires beforeprint. */
    public const PRINT_JS = <<<'JS'
        () => {
            const css = [];
            const walk = (rules) => [...rules].forEach((rule) => {
                if (rule instanceof CSSMediaRule && rule.media.mediaText.includes('print')) css.push(...[...rule.cssRules].map((r) => r.cssText));
                else if (rule.cssRules) walk(rule.cssRules);
            });
            [...document.styleSheets].forEach((sheet) => { try { walk(sheet.cssRules); } catch (e) {} });
            const style = document.createElement('style');
            style.textContent = css.filter((text) => ! text.startsWith('@page')).join('\n');
            document.head.append(style);
            window.dispatchEvent(new Event('beforeprint'));
            return css.length;
        }
        JS;

    /** Every listing page the modules register (Menus › Module page), plus the kernel's own. */
    public static function pages(): array
    {
        return collect(app(Registry::class)->all('websitePage'))->pluck('path')
            ->push('/', '/search?q=software', '/no-such-page')
            ->unique()->values()->all();
    }

    /** One record of each most visited kind (Test Plan §7.2). */
    public static function records(array $u): array
    {
        return [$u['program']->url(), $u['notice']->url(), $u['post']->url(), $u['event']->url(), $u['scholarship']->url(),
            $u['circular']->url(), $u['faculty']->url(), $u['department']->url()];
    }

    /** The same addresses in Bangla (paths or full URLs). */
    public static function bangla(array $paths): array
    {
        return array_map(function (string $url): string {
            $path = (string) preg_replace('#^https?://[^/]+#', '', $url) ?: '/';

            return '/bn'.($path === '/' ? '' : $path);
        }, $paths);
    }

    /** @return array<string, mixed> the records, by name */
    public static function make(): array
    {
        $ug = ProgramFactory::level('Undergraduate');
        $local = FeeCategory::query()->create(['name' => ['en' => 'Local Tuition Fee'], 'code' => 'local', 'currency' => 'BDT']);

        $faculty = Faculty::factory()->create(['name' => ['en' => 'Faculty of Science and Information Technology'], 'short_name' => 'FSIT']);
        $department = Department::factory()->create(['faculty_id' => $faculty->id, 'name' => ['en' => 'Software Engineering'], 'short_name' => 'SWE']);
        $program = Program::factory()->create(['department_id' => $department->id, 'level_term_id' => $ug,
            'name' => ['en' => 'B. Sc. in Software Engineering', 'bn' => 'সফটওয়্যার ইঞ্জিনিয়ারিং-এ বি. এসসি.'], 'short_name' => 'BSCSWE']);

        $tuition = Tuition::query()->create(['program_id' => $program->id, 'fee_category_id' => $local->id, 'credit_min' => 147,
            'totals_overridden' => true, 'tuition_min' => 709500, 'total_min' => 952500]);
        TuitionLine::query()->create(['tuition_id' => $tuition->id, 'line_type' => TuitionLine::ADMISSION, 'label' => ['en' => 'Admission fee'], 'amount' => 61750]);

        $board = Board::query()->create(['level_term_id' => $ug, 'name' => ['en' => 'General Education Board']]);
        $result = WaiverCategory::query()->create(['level_term_id' => $ug, 'name' => ['en' => 'Result Based Waiver(SSC & HSC)']]);
        $rule = WaiverRule::query()->create(['name' => ['en' => 'GPA 5'], 'level_term_id' => $ug, 'fee_category_id' => $local->id,
            'ssc_min' => 2.5, 'ssc_max' => 5, 'hsc_min' => 5, 'hsc_max' => 5, 'waiver_percent' => 20]);
        $rule->syncCategories([$result->id]);

        // Notices, news and events open on the main site only once published there (core_relations site:0)
        $mainSite = fn (object $record): object => tap($record, fn () => app(RelationEngine::class)->sync($record, [], mainSite: true, user: null));

        return [
            'faculty' => $faculty,
            'department' => $department,
            'program' => $program,
            'tuition' => $tuition,
            'board' => $board,
            'waiver' => $result,
            'notice' => $mainSite(Notice::factory()->create(['title' => ['en' => 'Mid-term exam schedule']])),
            'post' => $mainSite(Post::factory()->create(['title' => ['en' => 'DIU wins the robotics contest']])),
            'event' => $mainSite(Event::factory()->create(['title' => ['en' => 'Career fair']])),
            'faq' => Faq::factory()->create(),
            'scholarship' => Scholarship::factory()->create(),
            'circular' => Circular::factory()->create(),
        ];
    }
}
