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
 * @internal Helper class to retrieve data from JSON payload
 */
class JsonRequestUtils
{
    /**
     * @var PropertyAccessor|null
     */
    private static $propertyAccessor;

    public static function isJsonRequest(Request $request): bool
    {
        return false !== strpos((string) $request->getContentType(), 'json');
    }

    /**
     * Read data from a JSON payload.
     * Paths like foo.bar will be evaluated to find deeper items in nested data structures.
     *
     * @return scalar|null
     */
    public static function getJsonPayloadValue(Request $request, string $parameterName)
    {
        $data = json_decode((string) $request->getContent());
        if (!$data instanceof \stdClass) {
            throw new BadRequestException('Invalid JSON.');
        }

        if (null === self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        try {
            $value = self::$propertyAccessor->getValue($data, $parameterName);
        } catch (AccessException $e) {
            return null;
        }

        if (null !== $value && !\is_scalar($value)) {
            throw new BadRequestException('Invalid JSON data, expected a scalar value.');
        }

        return $value;
    }
}
