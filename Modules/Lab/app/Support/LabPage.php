<?php

namespace Modules\Lab\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Lab\Models\Lab;
use Modules\Lab\Models\LabItem;
use Platform\Assignments\PersonView;
use Platform\Media\MediaItem;
use Platform\Relations\RelationEngine;

/**
 * What a lab's page shows, ready for a theme (`$page` in lab::microsite.home, and in lab::listing
 * for a department's first lab): the department's other labs for the side list, the texts made
 * safe, the in-charge, what else the lab has (equipment, scopes, projects, products), pictures of
 * its albums. Themes hold no PHP logic (T-1), so everything worked out lives here.
 */
class LabPage
{
    public const GALLERY = 9;

    protected array $memo = [];

    public function __construct(public Lab $lab) {}

    public function department(): ?Model
    {
        return $this->memo['department'] ??= $this->lab->department();
    }

    /** /fsit/cse/labs, else /labs */
    public function labsUrl(): string
    {
        return LabsPage::departmentUrl($this->department());
    }

    public function heroText(): string
    {
        return LabsPage::departmentHero($this->department());
    }

    /** @return list<array{name: string, url: string, current: bool}> the labs of the lab's department (or the labs without one), this one marked */
    public function siblings(): array
    {
        return $this->memo['siblings'] ??= Lab::query()->listed()->where('department_id', $this->lab->department_id)->get()
            ->map(fn (Lab $l): array => ['name' => (string) $l->name, 'url' => (string) $l->url(), 'current' => $l->is($this->lab)])
            ->values()->all();
    }

    public function type(): ?string
    {
        return $this->lab->termsIn(Lab::TYPE)->first()?->name;
    }

    /** The lab's text: its body, else its summary */
    public function description(): ?string
    {
        if (filled($this->lab->body)) {
            return Str::sanitizeHtml((string) $this->lab->body);
        }

        return filled($this->lab->summary) ? e((string) $this->lab->summary) : null;
    }

    public function instruments(): ?string
    {
        return filled($this->lab->equipment_summary) ? Str::sanitizeHtml((string) $this->lab->equipment_summary) : null;
    }

    public function location(): ?string
    {
        return filled($this->lab->location) ? (string) $this->lab->location : null;
    }

    /** @return Collection<int, PersonView> the in-charge first, then the team */
    public function people(): Collection
    {
        return $this->memo['people'] ??= $this->lab->people(['in_charge', 'team']);
    }

    /** @return list<array{label: string, url: string, count: int}> equipment, scopes, projects, products that have entries */
    public function explore(): array
    {
        $base = rtrim((string) $this->lab->url(), '/');
        $counts = $this->lab->items()->getQuery()->where('is_active', true)->reorder()->selectRaw('item_type, count(*) as n')->groupBy('item_type')->pluck('n', 'item_type');

        return collect(LabItem::SEGMENTS)->filter(fn (string $segment, string $type): bool => ($counts[$type] ?? 0) > 0)
            ->map(fn (string $segment, string $type): array => ['label' => __('lab::lab.types_plural.'.$type), 'url' => $base.'/'.$segment, 'count' => (int) $counts[$type]])
            ->values()->all();
    }

    /** @return array<string, string> email / phone / website that are filled */
    public function contact(): array
    {
        return array_filter(['email' => $this->lab->email, 'phone' => $this->lab->phone, 'website' => $this->lab->website_url], 'filled');
    }

    public function video(): ?string
    {
        return $this->lab->videoEmbed();
    }

    public function image(): ?MediaItem
    {
        return $this->lab->firstMedia('cover');
    }

    /** @return list<array{url: string, caption: ?string}> pictures of the albums shown in the lab (Gallery module) */
    public function gallery(): array
    {
        $album = 'Modules\\Gallery\\Models\\Album';
        if (! class_exists($album)) {
            return [];
        }

        return $this->memo['gallery'] ??= app(RelationEngine::class)->for($this->lab->getMorphClass().':'.$this->lab->getKey())->content($album)->query()->get()
            ->flatMap(fn ($a) => $a->entries('image'))
            ->take(self::GALLERY)
            ->map(fn (array $e): array => ['url' => (string) ($e['item']->url('medium') ?? $e['item']->url()), 'caption' => $e['caption']])
            ->values()->all();
    }
}
