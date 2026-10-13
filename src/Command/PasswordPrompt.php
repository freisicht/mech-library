<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class PasswordPrompt
{
    public const MIN_LENGTH = 8;

    /**
     * Asks twice with hidden input, or reads one line from STDIN with
     * --password-stdin (for scripts and containers).
     */
    public static function read(InputInterface $input, OutputInterface $output, QuestionHelper $helper): string
    {
        if ($input->getOption('password-stdin') === true) {
            $password = rtrim((string) fgets(STDIN), "\r\n");
        } else {
            $password = (string) $helper->ask($input, $output, self::hidden('Password: '));
            $repeat   = (string) $helper->ask($input, $output, self::hidden('Repeat password: '));
            if ($password !== $repeat) {
                throw new \InvalidArgumentException('passwords do not match');
            }
        }
        if (mb_strlen($password) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException('password must be at least ' . self::MIN_LENGTH . ' characters');
        }
        return $password;
    }

    private static function hidden(string $prompt): Question
    {
        $q = new Question($prompt);
        $q->setHidden(true);
        return $q;
    }
}
