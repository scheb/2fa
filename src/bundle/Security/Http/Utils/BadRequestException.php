<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Utils;

use Symfony\Component\HttpFoundation\Exception\BadRequestException as SymfonyBadRequestException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable Symfony.Classes.MultipleClassesOneFile.Invalid
if (class_exists(SymfonyBadRequestException::class)) {
    /**
     * @internal Compatibility for Symfony >= 5.1
     */
    class BadRequestException extends SymfonyBadRequestException
    {
    }
} else {
    /**
     * @internal Compatibility for Symfony < 5.1
     */
    class BadRequestException extends \UnexpectedValueException implements RequestExceptionInterface
    {
    }
}
