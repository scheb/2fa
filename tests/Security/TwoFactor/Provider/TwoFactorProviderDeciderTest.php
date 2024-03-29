<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderDecider;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderDeciderInterface;
use Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Condition\AbstractAuthenticationContextTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class TwoFactorProviderDeciderTest extends AbstractAuthenticationContextTestCase
{
    private MockObject|TwoFactorProviderDeciderInterface $twoFactorProviderDecider;
    private MockObject|TwoFactorTokenInterface $twoFactorToken;

    protected function setUp(): void
    {
        $this->twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->twoFactorProviderDecider = new TwoFactorProviderDecider();
    }

    /**
     * @test
     */
    public function getPreferredTwoFactorProvider_implementsPreferredProvider_returnsPreferredProvider(): void
    {
        $user = $this->createUserWithPreferredProvider('preferredProvider');

        $this->assertEquals(
            'preferredProvider',
            $this->twoFactorProviderDecider->getPreferredTwoFactorProvider([], $this->twoFactorToken, $this->createAuthContext($user)),
        );
    }

    /**
     * @test
     */
    public function getPreferredTwoFactorProvider_implementsPreferredProvider_returnsNullPreferredProvider(): void
    {
        $user = $this->createUserWithPreferredProvider(null);

        $this->assertNull(
            $this->twoFactorProviderDecider->getPreferredTwoFactorProvider([], $this->twoFactorToken, $this->createAuthContext($user)),
        );
    }

    /**
     * @test
     */
    public function getPreferredTwoFactorProvider_unexpectedUserObject_returnsNull(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->assertNull(
            $this->twoFactorProviderDecider->getPreferredTwoFactorProvider([], $this->twoFactorToken, $this->createAuthContext($user)),
        );
    }

    private function createUserWithPreferredProvider(string|null $preferredProvider): MockObject|UserWithPreferredProviderInterface
    {
        $user = $this->createMock(UserWithPreferredProviderInterface::class);
        $user
            ->expects($this->any())
            ->method('getPreferredTwoFactorProvider')
            ->willReturn($preferredProvider);

        return $user;
    }

    private function createAuthContext(object $user): MockObject|AuthenticationContextInterface
    {
        $authContext = $this->createMock(AuthenticationContextInterface::class);
        $authContext
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        return $authContext;
    }
}
