<?php

declare(strict_types=1);

namespace App\Command;

use App\Bootstrap;
use App\Importer\UnitImporter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mul:import', description: 'Import cached raw JSON into MySQL')]
final class ImportCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'dir',
            InputArgument::OPTIONAL,
            'Directory with JSON files to import',
            $_ENV['MUL_RAW_CACHE_DIR'] ?? 'data/raw',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initPropel();

        $logger = new Logger('import');
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

        $dir = (string) $input->getArgument('dir');
        if (!str_starts_with($dir, '/')) {
            $dir = dirname(__DIR__, 2) . '/' . $dir;
        }

        if (!is_dir($dir)) {
            $output->writeln("<error>Directory not found: {$dir}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Importing from {$dir}…</info>");

        $importer = new UnitImporter($logger);
        $counts   = $importer->importDirectory($dir);

        $output->writeln(sprintf(
            '<info>Import complete: %d inserted, %d updated, %d skipped</info>',
            $counts['inserted'],
            $counts['updated'],
            $counts['skipped'],
        ));

        return Command::SUCCESS;
    }
}
