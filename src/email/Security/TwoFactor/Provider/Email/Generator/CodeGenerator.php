<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator;

use DateInterval;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\PersisterInterface;
use function method_exists;
use function random_int;

/**
 * @final
 */
class CodeGenerator implements CodeGeneratorInterface
{
    public function __construct(
        private readonly PersisterInterface $persister,
        private readonly AuthCodeMailerInterface $mailer,
        private readonly int $digits,
        private readonly string|null $expiresAfter = null,
        private readonly ClockInterface|null $clock = null,
    ) {
    }

    public function generateAndSend(TwoFactorInterface $user): void
    {
        $user->setEmailAuthCode((string) $this->doGenerateCode());

        if (method_exists($user, 'setEmailAuthCodeCreatedAt')) {
            $user->setEmailAuthCodeCreatedAt($this->now());
        }

        $this->persister->persist($user);
        $this->mailer->sendAuthCode($user);
    }

    public function reSend(TwoFactorInterface $user): void
    {
        if ($this->isCodeExpired($user) && method_exists($user, 'getEmailAuthCodeCreatedAt') && method_exists($user, 'setEmailAuthCodeCreatedAt')) {
            $user->setEmailAuthCode((string) $this->doGenerateCode());
            $user->setEmailAuthCodeCreatedAt($this->now());

            $this->persister->persist($user);
        }

        $this->mailer->sendAuthCode($user);
    }

    public function isCodeExpired(TwoFactorInterface $user): bool
    {
        if (null === $this->expiresAfter || !method_exists($user, 'getEmailAuthCodeCreatedAt') || !method_exists($user, 'setEmailAuthCodeCreatedAt')) {
            return false;
        }

        $now = $this->now();
        $expiresAt = $user->getEmailAuthCodeCreatedAt()?->add(new DateInterval($this->expiresAfter));

        return null !== $expiresAt && $now->getTimestamp() >= $expiresAt->getTimestamp();
    }

    protected function generateCode(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    private function doGenerateCode(): int
    {
        $min = 10 ** ($this->digits - 1);
        $max = 10 ** $this->digits - 1;

        return $this->generateCode($min, $max);
    }

    private function now(): DateTimeImmutable
    {
        return $this->clock?->now() ?? new DateTimeImmutable();
    }
}
