<?php

declare(strict_types=1);

namespace Chr15k\Typos\Console\Commands;

use Chr15k\Typos\Config;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'check')]
final class CheckCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('init') || ! Config::exists()) {
            return $this->initConfiguration($output);
        }

        $paths = $input->getArgument('paths');
        $paths = is_string($paths) ? $paths : '.';

        $write = (bool) $input->getOption('write');

        $output->writeln("\n<info>Scanning codebase for typos...</info>\n");

        $version = '1.47.2';
        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');

        // Match the exact naming convention of the official releases
        $binaryName = match (true) {
            $os === 'Darwin' && in_array($arch, ['arm64', 'aarch64'], true) => sprintf('typos-v%s-aarch64-apple-darwin', $version),
            $os === 'Darwin' && $arch === 'x86_64'                          => sprintf('typos-v%s-x86_64-apple-darwin', $version),
            $os === 'Linux' && in_array($arch, ['arm64', 'aarch64'], true)  => sprintf('typos-v%s-aarch64-unknown-linux-musl', $version),
            $os === 'Linux' && $arch === 'x86_64'                           => sprintf('typos-v%s-x86_64-unknown-linux-musl', $version),
            $os === 'Windows' && $arch === 'x86_64'                         => sprintf('typos-v%s-x86_64-pc-windows-msvc.exe', $version),
            default                                                         => null,
        };

        if (! $binaryName) {
            $output->writeln(sprintf('<error>❌ Unsupported OS/Architecture combination: %s (%s)</error>', $os, $arch));

            return Command::FAILURE;
        }

        // Resolves to the root bin/ directory from src/Console/Commands/CheckCommand.php
        $binaryPath = dirname(__DIR__, 3).'/bin/'.$binaryName.'/typos';

        if (! file_exists($binaryPath)) {
            $output->writeln(sprintf('<error>❌ Executable binary missing at: %s</error>', $binaryPath));

            return Command::FAILURE;
        }

        // Safeguard to ensure executable permissions are active on Linux/macOS
        if ($os !== 'Windows' && ! is_executable($binaryPath)) {
            @chmod($binaryPath, 0755);
        }

        $commandArgs = [$binaryPath, $paths];

        if ($write) {
            $commandArgs[] = '--write-changes';
        }

        try {
            $process = new Process($commandArgs);
            $process->setTimeout(120);
            $process->setTty(true);

            $exitCode = $process->run(function (string $type, string $buffer) use ($output): void {
                $output->write($buffer);
            });
        } catch (Exception $exception) {
            $output->writeln(sprintf('<error>❌ Runtime error: %s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }

        if ($exitCode !== 0) {
            $output->writeln("\n<error>❌ Spellcheck failed. Typos were discovered in code assets.</error>");

            return Command::FAILURE;
        }

        $output->writeln("\n<info>✅ Spellcheck complete. Clear skies!</info>");

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->setDescription('Run the blacklist spellchecker across the repository source files')
            ->addOption('init', 'i', InputOption::VALUE_NONE, 'Initialize a new configuration file.')
            ->addOption('write', 'w', InputOption::VALUE_NONE, 'Fix typos by writing changes directly to the files.')
            ->addArgument('paths', InputArgument::OPTIONAL, 'Paths to scan.')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file (defaults to project root).');
    }

    /*
     * Initialize the configuration file.
     */
    private function initConfiguration(OutputInterface $output): int
    {
        if (! Config::init()) {
            $output->writeln('<error>❌ Configuration file already exists.</error>');

            return Command::FAILURE;
        }

        $output->writeln("\n<info>✅ Configuration file has been created.</info>");
        $output->writeln("\n<info>Now you can specify the words or directories to ignore in [_typos.toml].</info>");
        $output->writeln("\n<info>Then run [./vendor/bin/typos] to check your project for typos.</info>");

        return Command::SUCCESS;
    }
}
