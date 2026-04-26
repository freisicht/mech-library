<?php

declare(strict_types=1);

namespace App\Command;

use App\Crawler\MulClientFactory;
use App\Crawler\Pager;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mul:scrape', description: 'Fetch raw unit JSON from MUL into data/raw/')]
final class ScrapeCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'types',
            null,
            InputOption::VALUE_REQUIRED,
            'Comma-separated Type ids to fetch',
            '18,45',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new Logger('scrape');
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

        $typeIds = array_map(
            'intval',
            explode(',', (string) $input->getOption('types'))
        );

        $client = MulClientFactory::create($logger);
        $pager  = new Pager($client, $typeIds, $logger);

        $output->writeln('<info>Starting MUL scrape…</info>');
        $units = $pager->fetchAll();

        // Write a single merged JSON file so the importer has one canonical source.
        $outDir  = $_ENV['MUL_RAW_CACHE_DIR'] ?? 'data/raw';
        if (!str_starts_with($outDir, '/')) {
            $outDir = dirname(__DIR__, 2) . '/' . $outDir;
        }

        $outFile = $outDir . '/units_all.json';
        $payload = json_encode(['Units' => $units], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($payload === false || file_put_contents($outFile, $payload) === false) {
            $output->writeln('<error>Failed to write output file</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Scrape complete: %d units written to %s</info>',
            count($units),
            $outFile,
        ));

        return Command::SUCCESS;
    }
}
