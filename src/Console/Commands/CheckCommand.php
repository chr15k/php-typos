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
use Symfony\Component\Process\Process;

#[AsCommand(name: 'check')]
final class CheckCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('init') || ! Config::exists()) {
            return $this->initConfiguration($output);
        }

        try {
            $binaryPath = BinaryResolver::getBinaryPath();
        } catch (Exception $exception) {
            $output->writeln(sprintf('<error>❌ Compatibility Error: %s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }

        $write = (bool) $input->getOption('write');
        $config = $input->getOption('config');

        $paths = $input->getArgument('paths');

        $diff = (bool) $input->getOption('diff');
        $format = $input->getOption('format');

        if ($write && $diff) {
            $output->writeln('<error>❌ --write and --diff cannot be used together.</error>');

            return Command::FAILURE;
        }

        $commandArgs = array_merge([$binaryPath], $paths ?: ['.']);

        if ($config) {
            $commandArgs[] = '--config';
            $commandArgs[] = $config;
        }

        if ($write) {
            $commandArgs[] = '--write-changes';
        }

        if ($diff) {
            $commandArgs[] = '--diff';
        }

        if ($format) {
            $commandArgs[] = '--format';
            $commandArgs[] = $format;
        }

        try {
            $process = new Process($commandArgs);
            $process->setTimeout(120);

            if (Process::isTtySupported()) {
                $process->setTty(true);
            }

            $exitCode = $process->run(function (string $type, string $buffer) use ($output): void {
                if ($type === Process::ERR) {
                    $output->write(sprintf('<error>%s</error>', $buffer));
                } else {
                    $output->write($buffer);
                }
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
            ->addOption('diff', 'd', InputOption::VALUE_NONE, 'Show a unified diff of proposed changes without writing.')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format (brief, long, json).', 'long')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file (defaults to project root).')
            ->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Paths to scan.');
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
