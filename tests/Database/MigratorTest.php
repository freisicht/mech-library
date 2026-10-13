<?php

declare(strict_types=1);

namespace App\Tests\Database;

use App\Bootstrap;
use App\Database\Migrator;
use PHPUnit\Framework\TestCase;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Propel;

final class MigratorTest extends TestCase
{
    private ConnectionInterface $conn;

    private string $dir;

    private string $suffix;

    protected function setUp(): void
    {
        if (!isset($_ENV['DB_USER']) && !getenv('DB_USER') && file_exists(dirname(__DIR__, 2) . '/.env')) {
            \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 2))->safeLoad();
        }
        Bootstrap::initPropel();
        $this->conn   = Propel::getConnection();
        $this->suffix = bin2hex(random_bytes(4));
        $this->dir    = sys_get_temp_dir() . '/ml_migrations_' . $this->suffix;
        mkdir($this->dir);

        $t = 'phpunit_mig_' . $this->suffix;
        file_put_contents($this->dir . '/001_create.sql', "-- create; with a semicolon in a comment\nCREATE TABLE {$t} (id INT NOT NULL); -- trailing;\n");
        file_put_contents($this->dir . '/002_alter.sql', "ALTER TABLE {$t} ADD name VARCHAR(10) NULL;\nINSERT INTO {$t} VALUES (1, 'a;b');\n");
        file_put_contents($this->dir . '/readme.txt', 'ignored');
    }

    protected function tearDown(): void
    {
        $this->conn->exec('DROP TABLE IF EXISTS phpunit_mig_' . $this->suffix);
        $this->conn->exec('DROP TABLE IF EXISTS phpunit_schema_' . $this->suffix);
        array_map(unlink(...), glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    public function testAppliesPendingOnceInOrder(): void
    {
        $m = $this->migrator();
        self::assertSame(['001_create.sql', '002_alter.sql'], $m->pending());

        foreach ($m->pending() as $file) {
            $m->apply($file);
        }

        self::assertSame([], $m->pending());
        $row = $this->conn->query('SELECT name FROM phpunit_mig_' . $this->suffix)->fetchColumn();
        self::assertSame('a;b', $row);
    }

    public function testBaselineSkipsOlderFiles(): void
    {
        $m = $this->migrator();
        self::assertSame(['001_create.sql'], $m->baseline('001'));
        self::assertSame(['002_alter.sql'], $m->pending());
    }

    public function testSplitDropsCommentsAndEmptyStatements(): void
    {
        self::assertSame(
            ['CREATE TABLE a (id INT)', 'DROP TABLE b'],
            Migrator::split("-- x; y\nCREATE TABLE a (id INT); -- z;\n\nDROP TABLE b;\n"),
        );
    }

    private function migrator(): Migrator
    {
        return new Migrator($this->conn, $this->dir, 'phpunit_schema_' . $this->suffix);
    }
}
