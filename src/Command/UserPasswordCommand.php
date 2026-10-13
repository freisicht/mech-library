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

#[AsCommand(name: 'user:password', description: 'Set a new password for a local login')]
final class UserPasswordCommand extends Command
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED)
            ->addOption('password-stdin', null, InputOption::VALUE_NONE, 'Read the password from STDIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = (string) $input->getArgument('username');
        $user     = $this->users->findLocal($username);
        if ($user === null) {
            $output->writeln("<error>no local user '{$username}'</error>");
            return Command::FAILURE;
        }

        try {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $this->users->setPassword($user, PasswordPrompt::read($input, $output, $helper));
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln("<info>Password updated for {$user->getUsername()}</info>");
        return Command::SUCCESS;
    }
}
