<?php

declare(strict_types=1);

namespace App\Tests\Web;

final class UnitListTest extends WebTestCase
{
    public function testListPageReturns200(): void
    {
        $response = $this->get('/');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Mech Library', (string) $response->getBody());
    }

    public function testListPageContainsFilterForm(): void
    {
        $body = (string) $this->get('/')->getBody();

        self::assertStringContainsString('filter-form', $body);
        self::assertStringContainsString('pv_min', $body);
        self::assertStringContainsString('ability-add-btn', $body);
    }

    public function testListPageContainsTableDiv(): void
    {
        $body = (string) $this->get('/')->getBody();

        self::assertStringContainsString('unit-table', $body);
        self::assertStringContainsString('tabulator', $body);
    }
}
