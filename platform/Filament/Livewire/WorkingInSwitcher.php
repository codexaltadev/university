<?php

namespace Platform\Filament\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Platform\Context\WorkingIn;

/**
 * "Working in ▾" in the admin top bar (UI/UX Part A §4.1): which microsite the microsite
 * screens open on. Shown to who may use at least one of them; switching reloads the page.
 */
class WorkingInSwitcher extends Component
{
    public ?string $owner = null;

    public string $returnTo = '';

    public function mount(): void
    {
        $this->owner = WorkingIn::pick(WorkingIn::keys());
        $this->returnTo = url()->current();
    }

    public function updatedOwner(?string $value): void
    {
        if (! in_array($value, WorkingIn::keys(), true)) {
            $this->owner = WorkingIn::pick(WorkingIn::keys());

            return;
        }

        WorkingIn::set($value);
        // A screen opened on another owner (?owner=…) follows the switch
        $this->redirect(preg_replace('/([?&])owner=[^&]*&?/', '$1', $this->returnTo) ?: $this->returnTo, navigate: false);
    }

    public static function shouldShow(): bool
    {
        if (WorkingIn::keys() === []) {
            return false;
        }

        return collect(WorkingIn::tools())->contains(fn (string $tool): bool => $tool::canAccess());
    }

    public function render(): View
    {
        return view('platform::filament.working-in', ['options' => WorkingIn::options()]);
    }
}
