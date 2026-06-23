<?php

declare(strict_types=1);

namespace Chr15k\Typos;

use Closure;
use Composer\Autoload\ClassLoader;

final class Config
{
    private const string CONFIGURATION_FILE = '_typos.toml';

    private static ?self $instance = null;

    private static string $projectPath;

    private static ?Closure $resolveConfigFilePathUsing = null;

    public function __construct()
    {
        self::$projectPath = dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]);
    }

    public static function resolveConfigFilePathUsing(Closure $closure): void
    {
        self::flush();

        self::$resolveConfigFilePathUsing = $closure;
    }

    public static function flush(): void
    {
        self::$instance = null;
        self::$resolveConfigFilePathUsing = null;
    }

    /**
     * Get the resolved absolute path to the configuration file location.
     */
    public static function getFilePath(): string
    {
        if (! isset(self::$projectPath)) {
            self::$projectPath = dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]);
        }

        return self::$projectPath.'/'.(self::$resolveConfigFilePathUsing instanceof Closure
            ? (self::$resolveConfigFilePathUsing)()
            : self::CONFIGURATION_FILE);
    }

    /**
     * Check if the configuration file already exists in the project root.
     */
    public static function exists(): bool
    {
        return file_exists(self::getFilePath());
    }

    /**
     * Get the active configuration instance tracker.
     */
    public static function instance(): self
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        return self::$instance = new self;
    }

    /**
     * Creates the configuration file for the user running the command by copying the package stub.
     */
    public static function init(): bool
    {
        $destinationPath = self::getFilePath();

        if (file_exists($destinationPath)) {
            return false;
        }

        $sourceStub = dirname(__DIR__).'/_typos.toml';

        if (! file_exists($sourceStub)) {
            return false;
        }

        return @copy($sourceStub, $destinationPath);
    }
}
