<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\RememberMe;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

/**
 * @final
 */
class RememberMeServicesDecorator implements RememberMeServicesInterface, LogoutHandlerInterface
{
    /**
     * @var RememberMeServicesInterface&LogoutHandlerInterface
     */
    private $decoratedRememberMeServices;

    /**
     * @param RememberMeServicesInterface&LogoutHandlerInterface $decoratedRememberMeServices
     */
    public function __construct($decoratedRememberMeServices)
    {
        $this->decoratedRememberMeServices = $decoratedRememberMeServices;
    }

    public function loginSuccess(Request $request, Response $response, TokenInterface $token): void
    {
        if ($token instanceof TwoFactorTokenInterface) {
            // Create a fake response to capture the remember-me cookie but not let it leak to the real response.
            $cookieCaptureResponse = new Response();
            $this->decoratedRememberMeServices->loginSuccess($request, $cookieCaptureResponse, $token);
            $rememberMeCookies = $cookieCaptureResponse->headers->getCookies();
            $token->setAttribute(TwoFactorTokenInterface::ATTRIBUTE_NAME_REMEMBER_ME_COOKIE, $rememberMeCookies);
        } else {
            // Not a TwoFactorToken => default behaviour
            $this->decoratedRememberMeServices->loginSuccess($request, $response, $token);
        }
    }

    public function autoLogin(Request $request): ?TokenInterface
    {
        return $this->decoratedRememberMeServices->autoLogin($request);
    }

    public function loginFail(Request $request, \Exception $exception = null): void
    {
        $this->decoratedRememberMeServices->loginFail($request, $exception);
    }

    public function logout(Request $request, Response $response, TokenInterface $token): void
    {
        $this->decoratedRememberMeServices->logout($request, $response, $token);
    }

    /**
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return ($this->decoratedRememberMeServices)->{$method}(...$arguments);
    }
}
