<?php

declare(strict_types=1);

namespace App\Command;

use App\Auth\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'user:add', description: 'Create a local login (AUTH_PROVIDER=local)')]
final class UserAddCommand extends Command
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED)
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Group membership, repeatable')
            ->addOption('email', null, InputOption::VALUE_REQUIRED)
            ->addOption('password-stdin', null, InputOption::VALUE_NONE, 'Read the password from STDIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = (string) $input->getArgument('username');
        /** @var list<string> $groups */
        $groups = $input->getOption('group');
        $email  = $input->getOption('email');

        try {
            if ($this->users->findLocal($username) !== null) {
                throw new \InvalidArgumentException("user '{$username}' already exists");
            }
            UserRepository::normalizeGroups($groups);
            /** @var QuestionHelper $helper */
            $helper   = $this->getHelper('question');
            $password = PasswordPrompt::read($input, $output, $helper);
            $user     = $this->users->createLocal($username, $password, $groups, is_string($email) ? $email : null);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Created %s</info>%s',
            $user->getUsername(),
            $groups === [] ? '' : ' (groups: ' . implode(', ', $this->users->groups($user)) . ')',
        ));
        return Command::SUCCESS;
    }
}
