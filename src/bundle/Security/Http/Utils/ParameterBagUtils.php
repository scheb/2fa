<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use function strpos;
use function substr;

/**
 * @internal Helper class analog to Symfony's ParameterBagUtils class
 *
 * @final
 */
class ParameterBagUtils
{
    private static PropertyAccessor|null $propertyAccessor = null;

    /**
     * @see \Symfony\Component\Security\Http\ParameterBagUtils
     *
     * Returns a request "parameter" value.
     * Paths like foo[bar] will be evaluated to find deeper items in nested data structures.
     */
    public static function getRequestParameterValue(Request $request, string $path): string|null
    {
        $pos = strpos($path, '[');
        if (false === $pos) {
            $value = self::getFromRequest($request, $path);

            return null === $value ? null : (string) $value;
        }

        $root = substr($path, 0, $pos);
        $value = self::getFromRequest($request, $root);
        if (null === $value) {
            return null;
        }

        self::$propertyAccessor ??= PropertyAccess::createPropertyAccessor();

        try {
            return self::$propertyAccessor->getValue($value, substr($path, $pos));
        } catch (AccessException) {
            return null;
        }
    }

    private static function getFromRequest(Request $request, string $path): mixed
    {
        $value = $request->query->all()[$path] ?? null;
        if (null !== $value) {
            return $value;
        }

        return $request->request->all()[$path] ?? null;
    }
}
