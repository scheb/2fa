<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @final
 *
 * @internal Helper class analog to Symfony's ParameterBagUtils class
 */
class ParameterBagUtils
{
    /**
     * @var PropertyAccessor|null
     */
    private static $propertyAccessor;

    /**
     * @see \Symfony\Component\Security\Http\ParameterBagUtils
     *
     * Returns a request "parameter" value.
     * Paths like foo[bar] will be evaluated to find deeper items in nested data structures.
     */
    public static function getRequestParameterValue(Request $request, string $path): ?string
    {
        if (false === $pos = strpos($path, '[')) {
            /** @psalm-suppress InternalMethod */

            return $request->get($path);
        }

        $root = substr($path, 0, $pos);

        /** @psalm-suppress InternalMethod */
        if (null === $value = $request->get($root)) {
            return null;
        }

        if (null === self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        try {
            return self::$propertyAccessor->getValue($value, substr($path, $pos));
        } catch (AccessException $e) {
            return null;
        }
    }
}
