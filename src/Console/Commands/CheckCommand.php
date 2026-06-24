<?php

declare(strict_types=1);

namespace Chr15k\Typos\Console\Commands;

use Chr15k\Typos\Config;
use Chr15k\Typos\Support\BinaryResolver;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'check')]
final class CheckCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('init') || ! Config::exists()) {
            return $this->initConfiguration($io);
        }

        $binaryPath = $this->resolveBinary($io);

        if ($binaryPath === null) {
            return Command::FAILURE;
        }

        if (! $this->validateOptions($input, $io)) {
            return Command::FAILURE;
        }

        try {
            $exitCode = $this->runTyposProcess($this->buildArgs($input, $binaryPath), $io);
        } catch (Exception $exception) {
            $io->error(sprintf('Runtime error: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        if ($exitCode !== 0) {
            $io->error('Typos detected. The scanned paths are grammatically unstable!');
            $io->outlineNote('Run with -w to automatically fix typos.');

            return Command::FAILURE;
        }

        $io->success('No typos detected. The scanned paths are grammatically stable!');

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->setDescription('Run the spellchecker across the repository source files')
            ->addOption('init', 'i', InputOption::VALUE_NONE, 'Initialize a new configuration file.')
            ->addOption('write', 'w', InputOption::VALUE_NONE, 'Fix typos by writing changes directly to the files.')
            ->addOption('diff', 'd', InputOption::VALUE_NONE, 'Show a unified diff of proposed changes without writing.')
            ->addOption('files', 'fi', InputOption::VALUE_NONE, 'Show the files being scanned.')
            ->addOption('identifiers', 'id', InputOption::VALUE_NONE, 'Show the identifiers from the scan.')
            ->addOption('words', 'wo', InputOption::VALUE_NONE, 'Show the words from the scan.')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format (brief, long, json).', 'long')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file (defaults to project root).')
            ->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Paths to scan.');
    }

    private function resolveBinary(SymfonyStyle $io): ?string
    {
        try {
            return BinaryResolver::getBinaryPath();
        } catch (Exception $exception) {
            $io->error(sprintf('Compatibility Error: %s', $exception->getMessage()));

            return null;
        }
    }

    /**
     * @param  list<string>  $args
     */
    private function runTyposProcess(array $args, SymfonyStyle $io): int
    {
        $process = new Process($args);
        $process->setTimeout(120);

        if (Process::isTtySupported()) {
            $process->setTty(true);
        }

        return $process->run(fn (string $type, string $buffer) => $io
            ->write($type === Process::ERR ? sprintf('<error>%s</error>', $buffer) : $buffer)
        );
    }

    /**
     * @return list<string>
     */
    private function buildArgs(InputInterface $input, string $binaryPath): array
    {
        /** @var list<string> $paths */
        $paths = (array) $input->getArgument('paths');

        $args = [$binaryPath, ...($paths ?: ['.'])];

        $this->appendValueOption($args, '--config', $input->getOption('config'));
        $this->appendFlag($args, '--write-changes', (bool) $input->getOption('write'));
        $this->appendFlag($args, '--diff', (bool) $input->getOption('diff'));
        $this->appendFlag($args, '--files', (bool) $input->getOption('files'));
        $this->appendFlag($args, '--identifiers', (bool) $input->getOption('identifiers'));
        $this->appendFlag($args, '--words', (bool) $input->getOption('words'));
        $this->appendValueOption($args, '--format', $input->getOption('format'));

        return $args;
    }

    /**
     * @param  list<string>  $args
     */
    private function appendFlag(array &$args, string $flag, bool $enabled): void
    {
        if ($enabled) {
            $args[] = $flag;
        }
    }

    /**
     * @param  list<string>  $args
     */
    private function appendValueOption(array &$args, string $flag, mixed $value): void
    {
        if (is_string($value) && $value !== '') {
            $args[] = $flag;
            $args[] = $value;
        }
    }

    private function validateOptions(InputInterface $input, SymfonyStyle $io): bool
    {
        if ($input->getOption('write') && $input->getOption('diff')) {
            $io->error('--write and --diff cannot be used together.');

            return false;
        }

        return true;
    }

    /*
     * Initialize the configuration file.
     */
    private function initConfiguration(SymfonyStyle $io): int
    {
        if (! Config::init()) {
            $io->error('Configuration file already exists.');

            return Command::FAILURE;
        }

        $io->info('✅ Configuration file has been created.');
        $io->info('Now you can specify the words or directories to ignore in [_typos.toml].');
        $io->info('Then run [./vendor/bin/typos] to check your project for typos.');

        return Command::SUCCESS;
    }
}
