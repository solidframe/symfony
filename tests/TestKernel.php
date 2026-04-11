<?php

declare(strict_types=1);

namespace SolidFrame\Symfony\Tests;

use SolidFrame\Symfony\SolidFrameBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new SolidFrameBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function ($container): void {
            $container->loadFromExtension('solid_frame', []);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/solidframe_test/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/solidframe_test/log';
    }
}
