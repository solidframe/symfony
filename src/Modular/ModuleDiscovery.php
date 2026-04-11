<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Modular;

use SolidFrame\Modular\Module\ModuleInterface;
use Symfony\Component\Finder\Finder;

final class ModuleDiscovery
{
    /**
     * Scan a directory for classes that implement ModuleInterface.
     *
     * Convention: each module directory contains a *Module.php file.
     * Example: modules/Billing/BillingModule.php
     *
     * @return list<class-string<ModuleInterface>>
     */
    public static function within(string $modulesPath, string $namespace): array
    {
        if (! is_dir($modulesPath)) {
            return [];
        }

        $modules = [];

        $finder = Finder::create()
            ->files()
            ->name('*Module.php')
            ->depth('== 1')
            ->in($modulesPath);

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePath();
            $className = $file->getBasename('.php');

            $fqcn = $namespace . '\\' . $relativePath . '\\' . $className;

            if (! class_exists($fqcn)) {
                continue;
            }

            if (! is_subclass_of($fqcn, ModuleInterface::class)) {
                continue;
            }

            $modules[] = $fqcn;
        }

        return $modules;
    }
}
