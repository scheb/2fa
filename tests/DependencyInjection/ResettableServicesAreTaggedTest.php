<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorServicesFactory;
use Scheb\TwoFactorBundle\DependencyInjection\SchebTwoFactorExtension;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Contracts\Service\ResetInterface;
use function class_exists;
use function implode;
use function sprintf;

/**
 * A service implementing {@see ResetInterface} is only reset when it carries the `kernel.reset` tag.
 *
 * The per-firewall services are `ChildDefinition`s, and a child does NOT inherit its parent's tags,
 * so tagging the abstract parent in `security.php` is not enough: the tag has to be re-added to each
 * child. Nothing fails at compile time when it is missing, and under PHP-FPM nothing fails at
 * runtime either, because every request builds a new container. The state only survives into the
 * next request under a persistent worker (FrankenPHP, RoadRunner, Swoole).
 *
 * This asserts the property rather than a list of known service ids: it resolves the child
 * definitions, then requires the tag of EVERY resolved definition whose class implements
 * `ResetInterface`. A future resettable service added without the tag fails here without anyone
 * having to remember to extend the test.
 */
class ResettableServicesAreTaggedTest extends TestCase
{
    private const string FIREWALL_NAME = 'firewallName';
    private const string USER_PROVIDER = 'userProvider';
    private const string RESET_TAG = 'kernel.reset';

    #[Test]
    public function createAuthenticator_resettableServiceDefinitions_allCarryTheResetTag(): void
    {
        $container = $this->compileContainerWithTwoFactorFirewall();

        $untagged = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$this->isResettable($definition)) {
                continue;
            }

            if ($definition->hasTag(self::RESET_TAG)) {
                continue;
            }

            $untagged[] = $id;
        }

        $this->assertSame([], $untagged, sprintf(
            'These services implement %s but carry no "%s" tag, so their state survives into the next request under a persistent worker: %s',
            ResetInterface::class,
            self::RESET_TAG,
            implode(', ', $untagged),
        ));
    }

    /**
     * The control for the arm above: without at least one resettable definition to look at, an empty
     * `$untagged` would pass against any container at all.
     */
    #[Test]
    public function createAuthenticator_resettableServiceDefinitions_areActuallyPresent(): void
    {
        $container = $this->compileContainerWithTwoFactorFirewall();

        $resettable = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$this->isResettable($definition)) {
                continue;
            }

            $resettable[] = $id;
        }

        $this->assertNotEmpty($resettable, 'No resettable service was found, so the assertion above examined nothing');
    }

    private function isResettable(Definition $definition): bool
    {
        $class = $definition->getClass();

        if (null === $class || !class_exists($class)) {
            return false;
        }

        return (new ReflectionClass($class))->implementsInterface(ResetInterface::class);
    }

    private function compileContainerWithTwoFactorFirewall(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        // Stub services the extension's configuration refers to.
        $container->setDefinition('acme_test.persister', new Definition());
        $container->setDefinition('acme_test.mailer', new Definition());

        $extension = new SchebTwoFactorExtension();
        $extension->load([[]], $container);

        $factory = new TwoFactorFactory(new TwoFactorServicesFactory());
        $factory->createAuthenticator($container, self::FIREWALL_NAME, [], self::USER_PROVIDER);
        $factory->createListeners($container, self::FIREWALL_NAME, []);

        // Resolves every ChildDefinition against its parent, which is what gives the per-firewall
        // definitions their class — and what drops any tag the child did not re-add.
        (new ResolveChildDefinitionsPass())->process($container);

        return $container;
    }
}
