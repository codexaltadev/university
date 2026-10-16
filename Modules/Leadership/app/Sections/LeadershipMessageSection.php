<?php

namespace Modules\Leadership\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Modules\Leadership\Models\LeadershipTerm;
use Modules\Leadership\Models\Position;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/**
 * Homepage section "Message from the Vice Chancellor / Chairman" (FR-LEAD-06): the current
 * holder of the chosen position — photo, name, position, the start of the message, "Read
 * more". A new holder's message shows as soon as they are appointed (AC-LEAD-01).
 */
class LeadershipMessageSection extends Section
{
    public static function key(): string
    {
        return 'leadership.message';
    }

    public static function label(): string
    {
        return __('leadership::leadership.section.label');
    }

    public static function view(): string
    {
        return 'leadership::sections.message';
    }

    public static function settingsSchema(): array
    {
        return [
            Select::make('position_id')->label(__('leadership::leadership.position.singular'))->required()
                ->options(fn (): array => Position::query()->ordered()->get()->mapWithKeys(fn (Position $p): array => [$p->id => $p->title])->all()),
            TextInput::make('words')->label(__('leadership::leadership.section.words'))->integer()->minValue(20)->maxValue(400)->default(80),
        ];
    }

    public function items(SectionContext $ctx): ?LeadershipTerm
    {
        $position = Position::query()->frontendVisible()->find((int) $ctx->setting('position_id'));

        return $position?->currentTerms()->with('assignment.person')->get()
            ->first(fn (LeadershipTerm $t): bool => (bool) $t->person()?->isActive() && filled($t->message));
    }
}
