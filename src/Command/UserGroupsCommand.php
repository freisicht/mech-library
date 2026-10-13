<?php

declare(strict_types=1);

namespace App\Command;

use App\Auth\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'user:groups',
    description: 'Show or replace the groups of a local login',
)]
final class UserGroupsCommand extends Command
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED)
            ->addArgument('groups', InputArgument::IS_ARRAY, 'New group list; replaces the current one')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Remove all groups')
            ->setHelp(<<<'TXT'
                Without groups the current memberships are printed.

                  bin/console user:groups alice                 # show
                  bin/console user:groups alice tuesday league  # set exactly these two
                  bin/console user:groups alice --clear         # remove all
                TXT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = (string) $input->getArgument('username');
        $user     = $this->users->findLocal($username);
        if ($user === null) {
            $output->writeln("<error>no local user '{$username}'</error>");
            return Command::FAILURE;
        }

        /** @var list<string> $groups */
        $groups = $input->getArgument('groups');
        if ($groups !== [] || $input->getOption('clear') === true) {
            try {
                $this->users->setGroups($user, $groups);
            } catch (\InvalidArgumentException $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            }
        }

        $current = $this->users->groups($user);
        $output->writeln($user->getUsername() . ': ' . ($current === [] ? '(no groups)' : implode(', ', $current)));
        return Command::SUCCESS;
    }
}
