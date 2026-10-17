<?php

namespace Modules\Newsletter\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Newsletter\Models\MailingList;
use Modules\Newsletter\Support\Subscriptions;

/**
 * Newsletter sign-up (FR-NLT-01): email (+ name), and the public lists to choose when there are
 * several. A confirmation email follows (FR-NLT-02). Limited per visitor (5 a minute) with a hidden
 * trap field. Used by the footer block, the /newsletter page and any page block.
 */
class SubscribeForm extends Component
{
    public string $email = '';

    public string $name = '';

    /** @var list<int|string> */
    public array $lists = [];

    /** Honeypot */
    public string $website = '';

    #[Locked]
    public string $source = 'footer';

    #[Locked]
    public bool $compact = false;

    public ?string $done = null;

    public function mount(string $source = 'footer', bool $compact = false): void
    {
        $this->source = mb_substr($source, 0, 30);
        $this->compact = $compact;
    }

    public function subscribe(Subscriptions $subscriptions): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:191'],
            'name' => ['nullable', 'string', 'max:191'],
            'lists' => ['array'],
        ], [], ['email' => __('newsletter::newsletter.fields.email')]);

        $key = 'newsletter-subscribe:'.request()->ip();
        if ($this->website !== '' || RateLimiter::tooManyAttempts($key, 5)) {
            $this->done = __('newsletter::newsletter.form.check_email');   // say nothing more to a bot

            return;
        }
        RateLimiter::hit($key, 60);

        $public = $this->publicLists()->modelKeys();
        $chosen = array_values(array_intersect(array_map('intval', $this->lists), $public));
        $subscriptions->subscribe($this->email, $chosen ?: ($public !== [] && count($public) === 1 ? $public : []), $this->source, $this->name ?: null);

        $this->reset('email', 'name', 'lists');
        $this->done = __('newsletter::newsletter.form.check_email');
    }

    protected function publicLists()
    {
        return MailingList::query()->where('is_active', true)->where('is_public', true)->ordered()->get();
    }

    public function render(): View
    {
        return view('newsletter::livewire.subscribe-form', ['publicLists' => $this->compact ? collect() : $this->publicLists()]);
    }
}
