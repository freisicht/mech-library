<?php

declare(strict_types=1);

namespace App\Command;

use App\Bootstrap;
use App\Crawler\MulClientFactory;
use App\Model\Eras;
use App\Model\ErasQuery;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mul:seed-eras', description: 'Parse /Era/Index and upsert into eras table')]
final class SeedErasCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initPropel();

        $logger = new Logger('seed-eras');
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

        $baseUrl = rtrim($_ENV['MUL_BASE_URL'] ?? 'https://masterunitlist.info', '/');
        $client  = MulClientFactory::create($logger);

        $html  = $client->fetchRaw($baseUrl . '/Era/Index');
        $items = $this->parseEras($html);

        if (empty($items)) {
            $output->writeln('<error>No eras parsed from HTML — check page structure</error>');
            return Command::FAILURE;
        }

        $upserted = 0;
        foreach ($items as [$id, $name]) {
            $existing = ErasQuery::create()->findPk($id);
            if ($existing !== null) {
                if ($existing->getName() === "Era #{$id}") {
                    // Upgrade stub name created by importer
                    $existing->setName($name);
                    $existing->save();
                }
                continue;
            }
            $era = new Eras();
            $era->setId($id);
            $era->setName($name);
            $era->save();
            $upserted++;
        }

        $output->writeln(sprintf('<info>Eras: %d inserted (out of %d parsed)</info>', $upserted, count($items)));

        return Command::SUCCESS;
    }

    /**
     * @return array<array{int, string}>
     */
    private function parseEras(string $html): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        $items = [];
        // MUL era list: <a href="/Era/EraUnits/N">Name</a>
        foreach ($xpath->query('//a[contains(@href, "/Era/EraUnits/")]') ?: [] as $node) {
            /** @var \DOMElement $node */
            $href = $node->getAttribute('href');
            if (preg_match('#/Era/EraUnits/(\d+)#', $href, $m)) {
                $items[] = [(int) $m[1], trim($node->textContent)];
            }
        }

        return array_unique($items, SORT_REGULAR);
    }
}
