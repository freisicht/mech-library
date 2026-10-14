<?php

declare(strict_types=1);

namespace App\Command;

use App\Bootstrap;
use App\Crawler\MulClientFactory;
use App\Database\ConnectionGuard;
use App\Importer\ImageDownloader;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mul:images', description: 'Download unit images to public/images/units/')]
final class ImagesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initPropel();

        $logger = new Logger('images');
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

        $imageDir = dirname(__DIR__, 2) . '/public/images/units';

        $client     = MulClientFactory::create($logger);
        $downloader = new ImageDownloader($client, $imageDir, $logger, new ConnectionGuard());

        $output->writeln('<info>Downloading images…</info>');
        $counts = $downloader->downloadAll();

        $output->writeln(sprintf(
            '<info>Images: %d downloaded, %d adopted from disk, %d skipped, %d failed</info>',
            $counts['downloaded'],
            $counts['adopted'],
            $counts['skipped'],
            $counts['failed'],
        ));

        return $counts['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
