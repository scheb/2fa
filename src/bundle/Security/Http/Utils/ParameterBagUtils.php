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
    private static ?PropertyAccessor $propertyAccessor = null;

    /**
     * @see \Symfony\Component\Security\Http\ParameterBagUtils
     *
     * Returns a request "parameter" value.
     * Paths like foo[bar] will be evaluated to find deeper items in nested data structures.
     */
    public static function getRequestParameterValue(Request $request, string $path): ?string
    {
        $pos = strpos($path, '[');
        if (false === $pos) {
            $value = ($request->query->all()[$path] ?? null) ?? ($request->request->all()[$path] ?? null);

            return null === $value ? null : (string) $value;
        }

        $root = substr($path, 0, $pos);
        $value = ($request->query->all()[$root] ?? null) ?? ($request->request->all()[$root] ?? null);
        if (null === $value) {
            return null;
        }

        if (null === self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        try {
            return self::$propertyAccessor->getValue($value, substr($path, $pos));
        } catch (AccessException) {
            return null;
        }
    }
}
