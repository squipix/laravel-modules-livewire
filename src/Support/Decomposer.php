<?php

namespace Mhmiton\LaravelModulesLivewire\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Decomposer
{
    protected $dependencies = ['livewire/livewire', 'nwidart/laravel-modules'];

    protected static function getCandidateRoots()
    {
        $roots = [
            base_path(),
            getcwd(),
            realpath(__DIR__.'/../..'),
        ];

        return array_values(array_unique(array_filter($roots, function ($root) {
            return is_string($root) && $root !== '';
        })));
    }

    public static function getComposerData($root = null)
    {
        $roots = $root ? [$root] : self::getCandidateRoots();

        foreach ($roots as $candidate) {
            $composerLock = rtrim($candidate, "\\/").'/composer.lock';

            if (! (new Filesystem())->exists($composerLock)) {
                continue;
            }

            try {
                $composer = (new Filesystem())->get($composerLock);

                return collect(data_get(json_decode($composer, true), 'packages'));
            } catch (\Exception $e) {
                continue;
            }
        }

        return collect([]);
    }

    public static function getPackage($packageName)
    {
        foreach (self::getCandidateRoots() as $root) {
            $vendorPath = rtrim($root, "\\/")."/vendor/{$packageName}";

            if (! \File::isDirectory($vendorPath)) {
                continue;
            }

            $packages = self::getComposerData($root);

            $version = $packages->firstWhere('name', $packageName)['version'] ?? null;

            return (object) [
                'name' => $packageName,
                'version' => $version ? Str::after($version, 'v') : null,
            ];
        }

        return null;
    }

    public static function hasPackage($packageName)
    {
        if (is_array($packageName)) {
            return self::hasPackages($packageName);
        }

        return self::getPackage($packageName) ? true : false;
    }

    public static function hasPackages($packageNames = [])
    {
        $packages = $packageNames ?? (new static())->dependencies;

        foreach ($packages as $v) {
            if (! self::getPackage($v)) {
                return false;
                break;
            }
        }

        return true;
    }

    public static function checkDependencies($packageNames = null)
    {
        $packages = $packageNames ?? (new static())->dependencies;

        $type = 'success';

        $output = '';

        if (! self::hasPackages($packages)) {
            $type = 'error';

            $output .= "\n<options=bold,reverse;fg=red> WHOOPS! </> 😳 \n";

            foreach ($packages as $package) {
                if (! self::hasPackage($package)) {
                    $name = Str::of($package)->after('/')->studly();

                    $output .= "\n<fg=red;options=bold>{$name} not found!</> \n";

                    $output .= "<fg=green;options=bold>Install the {$name} package - composer require {$package}</> \n";
                }
            }
        }

        return (object) ['type' => $type, 'message' => $output];
    }
}
