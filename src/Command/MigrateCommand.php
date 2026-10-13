<?php

declare(strict_types=1);

namespace App\Command;

use App\Database\Migrator;
use Propel\Runtime\Propel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'db:migrate', description: 'Apply pending SQL migrations from data/migrations/')]
final class MigrateCommand extends Command
{
    public function __construct(private readonly ?Migrator $migrator = null)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('baseline', null, InputOption::VALUE_REQUIRED, 'Mark migrations up to NNN as applied without running them')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only list pending migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrator = $this->migrator ?? new Migrator(Propel::getConnection(), dirname(__DIR__, 2) . '/data/migrations');

        $baseline = $input->getOption('baseline');
        if (is_string($baseline)) {
            if (preg_match('/^\d{3}$/', $baseline) !== 1) {
                $output->writeln('<error>--baseline expects a three-digit number like 003</error>');
                return Command::FAILURE;
            }
            foreach ($migrator->baseline($baseline) as $file) {
                $output->writeln("marked  {$file}");
            }
        }

        $pending = $migrator->pending();
        if ($pending === []) {
            $output->writeln('No pending migrations.');
            return Command::SUCCESS;
        }

        foreach ($pending as $file) {
            if ($input->getOption('dry-run')) {
                $output->writeln("pending {$file}");
                continue;
            }
            try {
                $migrator->apply($file);
            } catch (Throwable $e) {
                $output->writeln("<error>failed  {$file}: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }
            $output->writeln("applied {$file}");
        }

        return Command::SUCCESS;
    }
}
