<?php

declare(strict_types=1);

namespace App\Command;

use App\Auth\AuthConfig;
use App\Auth\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'user:list', description: 'List all users with provider and groups')]
final class UserListCommand extends Command
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Username', 'Provider', 'Groups', 'Last login']);
        foreach ($this->users->findAll() as $user) {
            $table->addRow([
                $user->getUsername(),
                $user->getProvider(),
                // Mattermost groups are channel IDs, a count is more useful than the list.
                $user->getProvider() === AuthConfig::LOCAL
                    ? implode(', ', $this->users->groups($user))
                    : count($this->users->groups($user)) . ' channels',
                $user->getLastLoginAt()?->format('Y-m-d H:i') ?? '',
            ]);
        }
        $table->render();
        return Command::SUCCESS;
    }
}
