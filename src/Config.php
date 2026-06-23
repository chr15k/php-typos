<?php

declare(strict_types=1);

namespace Chr15k\Typos;

use Composer\Autoload\ClassLoader;

final class Config
{
    private const string CONFIGURATION_FILE = '_typos.toml';

    private static ?string $projectPath = null;

    public static function flush(): void
    {
        self::$projectPath = null;
    }

    public static function getFilePath(): string
    {
        return self::projectPath().'/'.self::CONFIGURATION_FILE;
    }

    public static function exists(): bool
    {
        return file_exists(self::getFilePath());
    }

    public static function init(): bool
    {
        if (self::exists()) {
            return false;
        }

        $source = dirname(__DIR__).'/_typos.toml';

        if (! file_exists($source)) {
            return false;
        }

        return copy($source, self::getFilePath());
    }

    private static function projectPath(): string
    {
        return self::$projectPath ??= dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]);
    }
}
