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
        // <div class="… filterable …" data-link="/Faction/Details/{id}">…<strong>Name</strong></div>
        foreach ($xpath->query('//div[contains(@class, "filterable") and @data-link]') ?: [] as $node) {
            /** @var \DOMElement $node */
            if (!preg_match('#/Faction/Details/(\d+)#', $node->getAttribute('data-link'), $m)) {
                continue;
            }
            $strong = $xpath->query('.//strong', $node);
            $name   = $strong !== false && $strong->length > 0
                ? trim($strong->item(0)->textContent)
                : trim($node->textContent);
            if ($name === '') {
                continue;
            }
            $items[] = [(int) $m[1], $name];
        }

        return array_unique($items, SORT_REGULAR);
    }
}
