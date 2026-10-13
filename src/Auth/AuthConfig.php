<?php

declare(strict_types=1);

namespace App\Auth;

final class AuthConfig
{
    public const MATTERMOST = 'mattermost';
    public const DISCORD    = 'discord';
    public const LOCAL      = 'local';

    private function __construct(
        public readonly string $provider,
        public readonly string $mattermostBaseUrl,
    ) {
    }

    /**
     * Without AUTH_PROVIDER, installations that already configure Mattermost keep
     * using it, everyone else gets local accounts.
     */
    public static function resolve(string $provider, string $mattermostBaseUrl): self
    {
        $provider = strtolower(trim($provider));
        if ($provider === '') {
            $provider = $mattermostBaseUrl !== '' ? self::MATTERMOST : self::LOCAL;
        }
        if (!in_array($provider, [self::MATTERMOST, self::DISCORD, self::LOCAL], true)) {
            throw new \RuntimeException("AUTH_PROVIDER '{$provider}' is not supported (local, discord, mattermost)");
        }
        return new self($provider, $mattermostBaseUrl);
    }

    public function isMattermost(): bool
    {
        return $this->provider === self::MATTERMOST;
    }

    public function isDiscord(): bool
    {
        return $this->provider === self::DISCORD;
    }

    /** Providers that log in through an OAuth redirect instead of the password form. */
    public function isOAuth(): bool
    {
        return !$this->isLocal();
    }

    public function isLocal(): bool
    {
        return $this->provider === self::LOCAL;
    }

    public function mattermostHost(): string
    {
        return (string) parse_url($this->mattermostBaseUrl, PHP_URL_HOST);
    }
}
