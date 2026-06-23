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
        // If we already figured this out during this request lifecycle, return it instantly
        if (self::$cachedPath !== null) {
            return self::$cachedPath;
        }

        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');

        $isArm = in_array($arch, ['arm64', 'aarch64'], true);
        $isX86 = in_array($arch, ['x86_64', 'amd64', 'AMD64'], true);

        $binary = match (true) {
            $os === 'Darwin' && $isArm  => sprintf('typos-v%s-aarch64-apple-darwin', self::ENGINE_VERSION),
            $os === 'Darwin' && $isX86  => sprintf('typos-v%s-x86_64-apple-darwin', self::ENGINE_VERSION),
            $os === 'Linux' && $isArm   => sprintf('typos-v%s-aarch64-unknown-linux-musl', self::ENGINE_VERSION),
            $os === 'Linux' && $isX86   => sprintf('typos-v%s-x86_64-unknown-linux-musl', self::ENGINE_VERSION),
            $os === 'Windows' && $isX86 => sprintf('typos-v%s-x86_64-pc-windows-msvc', self::ENGINE_VERSION),
            default                     => null,
        };

        if ($binary === null) {
            throw new RuntimeException(
                sprintf('The platform "%s (%s)" is currently not supported by this package.', $os, $arch)
            );
        }

        $file = $os === 'Windows' ? 'typos.exe' : 'typos';
        $absolutePath = dirname(__DIR__, 2).sprintf('/bin/%s/%s', $binary, $file);

        if (! file_exists($absolutePath)) {
            throw new RuntimeException(
                sprintf('Required native binary asset is missing from the package tracking: %s', $binary)
            );
        }

        if ($os !== 'Windows' && ! is_executable($absolutePath)) {
            @chmod($absolutePath, 0755);
        }

        return self::$cachedPath = $absolutePath;
    }
}
