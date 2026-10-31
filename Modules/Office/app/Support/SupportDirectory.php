<?php

namespace Modules\Office\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Office\Models\OfficeSupportContact;
use Platform\Assignments\PersonView;

/**
 * /support-contacts ready for a theme (`$page` in office::support-contacts), as DIU's /support-contact: a card per person
 * of each purpose (name, designation, office, where, phone, emails, extension, the purpose), a purpose without people as a
 * card of its office; the purposes and offices to filter by; the search and filters asked for in the address (?q, ?purpose,
 * ?office). Title, text and picture from Settings › Offices. Themes hold no PHP logic (T-1).
 */
class SupportDirectory
{
    /** @var list<array<string, mixed>>|null */
    protected ?array $cards = null;

    /** @param  Collection<int, OfficeSupportContact>  $contacts  active, of visible offices, in order, with office */
    public function __construct(protected Collection $contacts, protected array $query = []) {}

    public function title(): string
    {
        return (string) (settings('office.support_title') ?: __('office::office.support.plural'));
    }

    public function subtitle(): ?string
    {
        return filled($text = settings('office.support_subtitle')) ? (string) $text : null;
    }

    public function image(): ?string
    {
        return media_url(settings('office.support_image'), 'large');
    }

    /**
     * @return list<array{name: string, designation: ?string, office: string, office_id: int, location: ?string, phone: ?string,
     *     tel: ?string, emails: list<string>, extension: ?string, purpose: string, search: string}>
     */
    public function cards(): array
    {
        return $this->cards ??= $this->contacts->flatMap(function (OfficeSupportContact $contact): array {
            $office = $contact->office;
            $base = [
                'office' => (string) $office->name,
                'office_id' => (int) $office->id,
                'location' => filled($contact->location) ? (string) $contact->location : (filled($office->location) ? (string) $office->location : null),
                'purpose' => (string) $contact->purpose,
            ];
            $people = $contact->people(OfficeSupportContact::ROLE);

            if ($people->isEmpty()) {
                return [$this->card($base + ['name' => $base['office'], 'designation' => null], $office->phone, $office->email, $office->extension)];
            }

            return $people->map(fn (PersonView $p): array => $this->card($base + ['name' => $p->name(), 'designation' => $p->designation()], $p->phone(), $p->email(), $p->extension()))->all();
        })->values()->all();
    }

    /** @return list<string> every purpose once, in order */
    public function purposes(): array
    {
        return collect($this->cards())->pluck('purpose')->filter()->unique()->values()->all();
    }

    /** @return list<array{id: int, name: string}> the offices with contacts, in order */
    public function offices(): array
    {
        return collect($this->cards())->unique('office_id')->map(fn (array $c): array => ['id' => $c['office_id'], 'name' => $c['office']])->values()->all();
    }

    /** @return array<int, string> office id => name, for the filter chip */
    public function officeNames(): array
    {
        return array_column($this->offices(), 'name', 'id');
    }

    /** @return list<array{0: string, 1: string, 2: string}> per card: its search text, purpose and office id, for filtering in place */
    public function filterIndex(): array
    {
        return array_map(fn (array $c): array => [$c['search'], $c['purpose'], (string) $c['office_id']], $this->cards());
    }

    /** @return array{q: string, purpose: string, office: string} the search and filters the page opens with */
    public function filters(): array
    {
        $purpose = (string) ($this->query['purpose'] ?? '');
        $office = (string) ($this->query['office'] ?? '');

        return [
            'q' => Str::limit(trim((string) ($this->query['q'] ?? '')), 100, ''),
            'purpose' => in_array($purpose, $this->purposes(), true) ? $purpose : '',
            'office' => in_array((int) $office, array_column($this->offices(), 'id'), true) ? $office : '',
        ];
    }

    protected function card(array $card, ?string $phone, ?string $email, ?string $extension): array
    {
        $emails = collect(explode(',', (string) $email))->map(fn (string $e): string => trim($e))->filter()->values()->all();

        return $card + [
            'phone' => filled($phone) ? (string) $phone : null,
            'tel' => filled($phone) ? preg_replace('/[^0-9+]/', '', (string) $phone) : null,
            'emails' => $emails,
            'extension' => filled($extension) ? (string) $extension : null,
            'search' => mb_strtolower(implode(' ', array_filter([$card['name'], $card['designation'], $card['office'], $card['purpose'], $card['location']]))),
        ];
    }
}
