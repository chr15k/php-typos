<?php

declare(strict_types=1);

namespace Chr15k\Typos\Support;

use RuntimeException;

final class BinaryResolver
{
    /**
     * The tracked version of the underlying typos engine.
     */
    private const string ENGINE_VERSION = '1.47.2';

    /**
     * @var array<string, string>
     */
    private const array TARGETS = [
        'Darwin-arm64'   => 'aarch64-apple-darwin',
        'Darwin-x86_64'  => 'x86_64-apple-darwin',
        'Linux-arm64'    => 'aarch64-unknown-linux-musl',
        'Linux-x86_64'   => 'x86_64-unknown-linux-musl',
        'Windows-x86_64' => 'x86_64-pc-windows-msvc',
    ];

    /**
     * The cached absolute path to the resolved binary.
     */
    private static ?string $cachedPath = null;

    /**
     * Resolve the absolute path to the appropriate native binary for the current platform.
     *
     * @throws RuntimeException If the current platform is unsupported or the binary is missing.
     */
    public static function getBinaryPath(): string
    {
        if (self::$cachedPath !== null) {
            return self::$cachedPath;
        }

        $path = self::resolveTargetBinary();

        return self::$cachedPath = $path;
    }

    private static function resolveTargetBinary(): string
    {
        $os = PHP_OS_FAMILY;
        $arch = self::normalizeArchitecture(php_uname('m'));

        $target = self::TARGETS[sprintf('%s-%s', $os, $arch)] ?? null;

        if ($target === null) {
            throw new RuntimeException(
                sprintf(
                    'The platform "%s (%s)" is currently not supported by this package.',
                    $os,
                    $arch
                )
            );
        }

        $binaryDirectory = sprintf('typos-v%s-%s', self::ENGINE_VERSION, $target);
        $binaryName = $os === 'Windows' ? 'typos.exe' : 'typos';
        $path = sprintf('%s/bin/%s/%s', dirname(__DIR__, 2), $binaryDirectory, $binaryName);

        self::ensureBinaryExists($path, $binaryDirectory, $os);

        return $path;
    }

    private static function normalizeArchitecture(string $architecture): string
    {
        return match ($architecture) {
            'arm64', 'aarch64'         => 'arm64',
            'x86_64', 'amd64', 'AMD64' => 'x86_64',
            default                    => $architecture,
        };
    }

    /**
     * @throws RuntimeException
     */
    private static function ensureBinaryExists(
        string $path,
        string $binaryDirectory,
        string $os
    ): void {
        if (! file_exists($path)) {
            throw new RuntimeException(
                sprintf(
                    'Required native binary asset is missing from the package: %s',
                    $binaryDirectory
                )
            );
        }

        if ($os === 'Windows' || is_executable($path)) {
            return;
        }

        chmod($path, 0755);
    }
}
