<?php

namespace Platform\Registry;

use Nwidart\Modules\Facades\Module;
use Platform\Assignments\ManualPerson;
use Platform\Contracts\ModuleExtension;
use Platform\Modules\Manifest;

/**
 * Runs the Extension of every active module once per request (SAD Part A §7.1).
 */
class ExtensionLoader
{
    protected bool $loaded = false;

    /** @var list<\Closure(Registrar): void> extra registrations (tests, kernel add-ons), kept across reloads */
    protected array $extensions = [];

    public function __construct(protected Registry $registry) {}

    /** Add registrations outside a module (survives reload()). */
    public function extend(string $owner, \Closure $register): void
    {
        $this->extensions[] = fn () => $register(new Registrar($this->registry, $owner));

        if ($this->loaded) {
            end($this->extensions)();
        }
    }

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        // The kernel's own registrations (ADR-14: manual persons are a person source)
        $this->registry->add('personSource', 'core', ['model' => ManualPerson::class]);

        foreach (Module::allEnabled() as $module) {
            $this->loadOne(Manifest::fromDirectory($module->getPath()));
        }

        foreach ($this->extensions as $extension) {
            $extension();
        }
    }

    public function loadOne(Manifest $manifest, ?Registry $into = null): void
    {
        $class = $manifest->extensionClass();

        if (! class_exists($class)) {
            return;
        }

        $extension = new $class;

        if ($extension instanceof ModuleExtension) {
            $extension->register(new Registrar($into ?? $this->registry, $manifest->slug()));
        }
    }

    /** After activate / deactivate in the same request. */
    public function reload(): void
    {
        $this->registry->flush();
        $this->loaded = false;
        $this->load();
    }

    /**
     * Manifest "provides" vs what the Extension registers (§10.2).
     *
     * @return list<string> mismatches
     */
    public function mismatches(Manifest $manifest): array
    {
        $scratch = new Registry;
        $this->loadOne($manifest, $scratch);

        $registered = $scratch->forModule($manifest->slug());
        $provides = $manifest->provides();
        $errors = [];

        foreach (Registrar::PROVIDES as $kind => $key) {
            $declared = ! empty($provides[$key]);
            $done = ! empty($registered[$kind]);

            if ($declared && ! $done) {
                $errors[] = "module.json declares \"{$key}\" but the Extension does not register it ({$kind}).";
            }

            if ($done && ! $declared) {
                $errors[] = "The Extension registers {$kind} but module.json does not declare \"{$key}\".";
            }
        }

        return $errors;
    }
}
