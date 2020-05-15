<?php

declare(strict_types=1);

namespace App\Tests;

class TwoFactorAccessControlTest extends TestCase
{
    public function test2faProtectsSecuredPath(): void
    {
        $this->setAll2faProvidersEnabled(true);

        $pageAfterLogin = $this->performLogin();
        $this->assert2faIsRequired($pageAfterLogin);

        $page = $this->navigateToSecuredPath();
        $this->assert2faIsRequired($page);
    }

    public function test2faProtectsPathWithoutAnyAccessControl(): void
    {
        $this->setAll2faProvidersEnabled(true);

        $pageAfterLogin = $this->performLogin();
        $this->assert2faIsRequired($pageAfterLogin);

        $page = $this->navigateToPathWithoutAnyAccessControl();
        $this->assert2faIsRequired($page);
    }

    public function test2faAllowsAccessToPageWithAccessControlAnonymous(): void
    {
        $this->setAll2faProvidersEnabled(true);

        $pageAfterLogin = $this->performLogin();
        $this->assert2faIsRequired($pageAfterLogin);

        $page = $this->navigateToPathWithAnonymousAccessControl();
        $this->assertIsAlwaysAccessiblePage($page);
    }

    public function test2faAllowsAccessToPageWithAccessControl2faInProgress(): void
    {
        $this->setAll2faProvidersEnabled(true);

        $pageAfterLogin = $this->performLogin();
        $this->assert2faIsRequired($pageAfterLogin);

        $page = $this->navigateToRouteWith2faInProgressAccessControl();
        $this->assertIs2faInProgressPage($page);
    }

    public function testDenyAnonymousUserAccessToPageWithAccessControl2faInProgress(): void
    {
        $page = $this->navigateToRouteWith2faInProgressAccessControl();
        $this->assertIsLoginPage($page);
    }

    public function testDenyFullyAuthenticatedUserAccessToPageWithAccessControl2faInProgress(): void
    {
        $this->setAll2faProvidersEnabled(false);

        $this->performLogin();

        $this->navigateToRouteWith2faInProgressAccessControl();
        $this->assertResponseIs403Forbidden();
    }
}
