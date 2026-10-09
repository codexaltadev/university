<?php

namespace Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Registry\ExtensionLoader;
use Platform\Registry\Registrar;
use Platform\Scope\ScopeResolver;
use Tests\Fixtures\Models\FixtureChild;
use Tests\Fixtures\Models\FixtureParent;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Fixture entities behave like Faculty (parent) and Department (child)
        Relation::morphMap([
            'test.parent' => FixtureParent::class,
            'test.child' => FixtureChild::class,
        ]);

        $this->app->make(ExtensionLoader::class)->extend('test', function (Registrar $r): void {
            $r->relationTarget(FixtureParent::class);
            $r->relationTarget(FixtureChild::class, parent: FixtureParent::class, parentKey: 'parent_id');
            $r->assignable(FixtureChild::class, roles: ['head', 'coordinator', 'officer']);
            $r->micrositeEntity(FixtureParent::class, routePrefix: 'test-faculty');
            $r->micrositeEntity(FixtureChild::class, routePrefix: 'test-dept', parent: FixtureParent::class);
        });

        $this->app->make(EntityHierarchy::class)->watch();
        $this->app->make(EntityHierarchy::class)->flush();
        $this->app->make(ScopeResolver::class)->forget();
    }
}
