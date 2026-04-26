<?php

declare(strict_types=1);

namespace App\Command;

use App\Bootstrap;
use App\Crawler\MulClientFactory;
use App\Model\Factions;
use App\Model\FactionsQuery;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mul:seed-factions', description: 'Parse /Faction/Index and upsert into factions table')]
final class SeedFactionsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initPropel();

        $logger = new Logger('seed-factions');
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

        $baseUrl = rtrim($_ENV['MUL_BASE_URL'] ?? 'https://masterunitlist.info', '/');
        $client  = MulClientFactory::create($logger);

        $html  = $client->fetchRaw($baseUrl . '/Faction/Index');
        $items = $this->parseFactions($html);

        if (empty($items)) {
            $output->writeln('<error>No factions parsed from HTML — check page structure</error>');
            return Command::FAILURE;
        }

        $upserted = 0;
        foreach ($items as [$id, $name]) {
            $existing = FactionsQuery::create()->findPk($id);
            if ($existing !== null) {
                continue;
            }
            $faction = new Factions();
            $faction->setId($id);
            $faction->setName($name);
            $faction->save();
            $upserted++;
        }

        $output->writeln(sprintf('<info>Factions: %d inserted (out of %d parsed)</info>', $upserted, count($items)));

        return Command::SUCCESS;
    }

    /**
     * @return array<array{int, string}>
     */
    private function parseFactions(string $html): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        $items = [];
        // MUL faction list: <a href="/Faction/FactionUnits/N">Name</a>
        foreach ($xpath->query('//a[contains(@href, "/Faction/FactionUnits/")]') ?: [] as $node) {
            /** @var \DOMElement $node */
            $href = $node->getAttribute('href');
            if (preg_match('#/Faction/FactionUnits/(\d+)#', $href, $m)) {
                $items[] = [(int) $m[1], trim($node->textContent)];
            }
        }

        return array_unique($items, SORT_REGULAR);
    }
}
