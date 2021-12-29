<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\PersisterInterface;
use function random_int;

/**
 * @final
 */
class CodeGenerator implements CodeGeneratorInterface
{
    public function __construct(
        private PersisterInterface $persister,
        private AuthCodeMailerInterface $mailer,
        private int $digits,
    ) {
    }

    public function generateAndSend(TwoFactorInterface $user): void
    {
        $min = 10 ** ($this->digits - 1);
        $max = 10 ** $this->digits - 1;
        $code = $this->generateCode($min, $max);
        $user->setEmailAuthCode((string) $code);
        $this->persister->persist($user);
        $this->mailer->sendAuthCode($user);
    }

    public function reSend(TwoFactorInterface $user): void
    {
        $this->mailer->sendAuthCode($user);
    }

    protected function generateCode(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
