<?php

declare(strict_types=1);

namespace App\Command;

use App\Doctor\Doctor;
use App\Doctor\Status;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:doctor', description: 'Check PHP, configuration, database, login setup and directories')]
final class DoctorCommand extends Command
{
    private const MARKS = [
        'ok'   => '<info>  ok </info>',
        'warn' => '<comment>warn </comment>',
        'fail' => '<error>fail </error>',
    ];

    /** @param array<string, string>|null $env */
    public function __construct(
        private readonly ?array $env = null,
        private readonly ?string $rootDir = null,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $doctor  = new Doctor($this->env ?? self::environment(), $this->rootDir ?? dirname(__DIR__, 2));
        $results = $doctor->run();

        $section = null;
        $counts  = ['ok' => 0, 'warn' => 0, 'fail' => 0];
        foreach ($results as $r) {
            if ($r->section !== $section) {
                $output->writeln(($section === null ? '' : "\n") . "<options=bold>{$r->section}</>");
                $section = $r->section;
            }
            $output->writeln(self::MARKS[$r->status->value] . $r->message);
            if ($r->hint !== null) {
                $output->writeln('      ' . $r->hint);
            }
            $counts[$r->status->value]++;
        }

        $output->writeln(sprintf(
            "\n%d ok, %d %s, %d %s",
            $counts['ok'],
            $counts['warn'],
            $counts['warn'] === 1 ? 'warning' : 'warnings',
            $counts['fail'],
            $counts['fail'] === 1 ? 'problem' : 'problems',
        ));
        $failed = array_filter($results, static fn ($r): bool => $r->status === Status::Fail) !== [];
        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return array<string, string> */
    private static function environment(): array
    {
        $env = [];
        // Same precedence as the rest of the app: $_ENV first, then the process environment.
        foreach (array_merge(getenv(), $_ENV) as $key => $value) {
            if (is_string($value)) {
                $env[(string) $key] = $value;
            }
        }
        return $env;
    }
}
