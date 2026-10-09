<?php

/*
| PersonPicker + PeopleManager on the Office form (FR-REL-09/10/11, UI/UX Part A §6):
| people with roles from the catalog, order, term, public flag, overrides; one current
| holder of a single-holder role; search across person sources and manual persons.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Office\Filament\Resources\Offices\Pages\EditOffice;
use Modules\Office\Models\Office;
use Platform\Assignments\Assignment;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Filament\Components\PersonPicker;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(RoleCatalog::class)->sync();                                     // office: head (one holder), team
    $this->actingAs(superAdmin());

    $this->office = Office::create(['name' => 'Registrar Office']);
    $this->head = ManualPerson::create(['name' => ['en' => 'Dr. Rahman'], 'designation' => ['en' => 'Registrar']]);
    $this->clerk = ManualPerson::create(['name' => ['en' => 'Ms. Akter'], 'designation' => ['en' => 'Officer']]);
    $this->next = ManualPerson::create(['name' => ['en' => 'Mr. Karim']]);
});

it('finds people in every source and labels them', function () {
    $results = PersonPicker::search('Rahman');

    expect($results)->toHaveKey(PersonPicker::keyOf($this->head))
        ->and($results[PersonPicker::keyOf($this->head)])->toContain('Dr. Rahman — Registrar')
        ->and(PersonPicker::resolve(PersonPicker::keyOf($this->clerk))?->is($this->clerk))->toBeTrue();
});

it('puts people in an office with roles, order, term and overrides', function () {
    Livewire::test(EditOffice::class, ['record' => $this->office->getRouteKey()])
        ->fillForm(['people' => [
            ['person' => PersonPicker::keyOf($this->clerk), 'role_key' => 'team', 'is_public' => true, 'overrides' => ['extension' => '201']],
            ['person' => PersonPicker::keyOf($this->head), 'role_key' => 'head', 'starts_on' => '2024-01-01', 'is_public' => true, 'role_label' => ['en' => 'Registrar']],
        ]])
        ->call('save')
        ->assertHasNoFormErrors();

    $people = $this->office->people();
    expect($people->map->name()->all())->toBe(['Dr. Rahman', 'Ms. Akter'])                        // catalog role order: head first
        ->and($people[1]->extension())->toBe('201')
        ->and(Assignment::where('role_key', 'head')->first()->getTranslation('role_label', 'en'))->toBe('Registrar');

    // The edit form shows what is saved, in order
    $rows = array_values(Livewire::test(EditOffice::class, ['record' => $this->office->getRouteKey()])->instance()->data['people']);
    expect(array_column($rows, 'role_key'))->toBe(['team', 'head'])
        ->and($rows[0]['overrides']['extension'])->toBe('201');
});

it('keeps one current holder of a single-holder role (Head)', function () {
    $page = Livewire::test(EditOffice::class, ['record' => $this->office->getRouteKey()])
        ->fillForm(['people' => [
            ['person' => PersonPicker::keyOf($this->head), 'role_key' => 'head', 'is_public' => true],
            ['person' => PersonPicker::keyOf($this->next), 'role_key' => 'head', 'is_public' => true],
        ]])
        ->call('save')
        ->assertHasFormErrors(['people']);

    expect(Assignment::count())->toBe(0);

    // Ending the previous term lets the new head in; the former one leaves the website list
    $page->fillForm(['people' => [
        ['person' => PersonPicker::keyOf($this->head), 'role_key' => 'head', 'ends_on' => now()->subDay()->toDateString(), 'is_public' => true],
        ['person' => PersonPicker::keyOf($this->next), 'role_key' => 'head', 'starts_on' => now()->toDateString(), 'is_public' => true],
    ]])->call('save')->assertHasNoFormErrors();

    expect($this->office->people()->map->name()->all())->toBe(['Mr. Karim'])
        ->and($this->office->people(former: true)->map->name()->all())->toBe(['Dr. Rahman']);
});

it('removes people taken off the list and hides those not public', function () {
    $this->office->assignments()->create(['person_type' => $this->clerk->getMorphClass(), 'person_id' => $this->clerk->id, 'role_key' => 'team', 'sort_order' => 1, 'is_public' => true]);

    Livewire::test(EditOffice::class, ['record' => $this->office->getRouteKey()])
        ->fillForm(['people' => [
            ['person' => PersonPicker::keyOf($this->head), 'role_key' => 'head', 'is_public' => false],
        ]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Assignment::where('person_id', $this->clerk->id)->exists())->toBeFalse()
        ->and($this->office->people())->toHaveCount(0);                                              // the head is not public
});
