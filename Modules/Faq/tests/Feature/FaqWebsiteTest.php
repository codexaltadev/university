<?php

/*
| M-28 FAQ on the website (SRS FR-FAQ-01..06, DB-05 v1.1 §13): /faq by category with the
| category text, instant search markup and FAQPage JSON-LD; an entity's /{entity}/faq and the
| Program "FAQ" tab; "Was this helpful?"; the FAQ block; Excel / CSV import.
*/

use Modules\Faq\Blocks\FaqBlock;
use Modules\Faq\Models\Faq;
use Modules\Faq\Support\FaqImporter;
use Modules\Menu\Support\MenuLinks;
use Modules\Program\Models\Program;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Vocabularies::ensureTerms(Faq::CATEGORY, [
        ['name' => ['en' => 'Admission'], 'slug' => 'admission'],
        ['name' => ['en' => 'Exam'], 'slug' => 'exam'],
    ]);
    $this->term = fn (string $name): int => (int) array_search($name, app(Vocabularies::class)->options(Faq::CATEGORY), true);
    Term::query()->find(($this->term)('Admission'))->update(['description' => ['en' => 'Questions before you apply.']]);

    $this->engine = app(RelationEngine::class);
    $this->onMain = function (string $question, ?string $category = null, array $attributes = []): Faq {
        $faq = Faq::factory()->create(['question' => ['en' => $question], 'answer' => ['en' => "<p>Answer to {$question}</p>"], ...$attributes]);
        $category && $faq->syncTerms(Faq::CATEGORY, [($this->term)($category)]);
        $this->engine->sync($faq, [], mainSite: true, user: null);

        return $faq;
    };
});

it('shows the main site\'s FAQs by category, with the category text and FAQPage JSON-LD (FR-FAQ-02/04)', function () {
    ($this->onMain)('How do I apply?', 'Admission');
    ($this->onMain)('When is the exam?', 'Exam');
    ($this->onMain)('Where is the library?');
    ($this->onMain)('Hidden one?', 'Exam', ['is_active' => false]);
    Faq::factory()->create(['question' => ['en' => 'Not on the main site?']]);

    $this->get('/faq')->assertOk()
        ->assertSeeInOrder(['Admission', 'Questions before you apply.', 'How do I apply?', 'Exam', 'When is the exam?', 'Other questions', 'Where is the library?'])
        ->assertSeeInOrder(['Frequently Asked', 'Questions', 'Find answers to common questions', 'Categories', 'All', '3', 'Admission', '1', 'Exam', '1', '1 question'])   // the default theme: DIU's /faq
        ->assertDontSee('Hidden one?')->assertDontSee('Not on the main site?')
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('data-search=', false);

    $this->get('/faq?category=exam')->assertOk()->assertSee('When is the exam?')->assertDontSee('How do I apply?');
    expect(app(MenuLinks::class)->modulePages())->toHaveKey('page:faq.index');
});

it('shows an entity\'s FAQs at its microsite and the Program FAQ tab (FR-FAQ-03)', function () {
    $program = Program::factory()->create();
    $faq = Faq::factory()->create(['question' => ['en' => 'Is there a lab fee?']]);
    $this->engine->sync($faq, [$program->getMorphClass().':'.$program->id], user: null);

    $this->get($program->url())->assertOk()->assertSee('Is there a lab fee?');
    $this->get(rtrim($program->department->url(), '/').'/faq')->assertNotFound();   // nothing shown in the department
});

it('counts "Was this helpful?" for visible FAQs only (FR-FAQ-05)', function () {
    $faq = ($this->onMain)('How do I apply?');
    $this->postJson(route('faq.helpful', $faq), ['answer' => 'yes'])->assertOk();
    $this->postJson(route('faq.helpful', $faq), ['answer' => 'no'])->assertOk();
    $this->postJson(route('faq.helpful', $faq), ['answer' => 'maybe'])->assertUnprocessable();
    expect($faq->fresh()->only(['helpful_yes', 'helpful_no']))->toBe(['helpful_yes' => 1, 'helpful_no' => 1]);

    $faq->update(['is_active' => false]);
    $this->postJson(route('faq.helpful', $faq), ['answer' => 'yes'])->assertNotFound();
});

it('puts FAQs on any page through the FAQ block', function () {
    $a = ($this->onMain)('How do I apply?', 'Admission');
    $e = ($this->onMain)('When is the exam?', 'Exam');

    expect(FaqBlock::faqs(['categories' => [($this->term)('Exam')]])->pluck('id')->all())->toBe([$e->id])
        ->and(FaqBlock::faqs(['faqs' => [$e->id, $a->id]])->pluck('id')->all())->toBe([$e->id, $a->id]);
});

it('imports FAQs from a file after checking it (FR-FAQ-06)', function () {
    $path = tempnam(sys_get_temp_dir(), 'faq').'.csv';
    $out = fopen($path, 'w');
    foreach ([['Question', 'Answer', 'Category'], ['How do I pay?', 'Online or at the bank.', 'Payment'], ['', 'No question', ''], ['When is the exam?', 'In June.', 'Exam']] as $row) {
        fputcsv($out, $row);
    }
    fclose($out);

    $checked = app(FaqImporter::class)->check($path);
    expect($checked['total'])->toBe(3)->and($checked['rows'])->toHaveCount(2)->and($checked['errors'][0])->toContain('Line 3');

    expect(app(FaqImporter::class)->save($checked['rows'], [], mainSite: true))->toBe(2);
    $pay = Faq::query()->where('question->en', 'How do I pay?')->sole();
    expect($pay->answer)->toBe('<p>Online or at the bank.</p>')
        ->and($pay->termsIn(Faq::CATEGORY)->first()->name)->toBe('Payment')
        ->and($pay->isOnMainSite())->toBeTrue();
});
