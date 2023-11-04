<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Utils;

use stdClass;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use function is_scalar;
use function json_decode;
use function str_contains;

/**
 * @internal Helper class to retrieve data from JSON payload
 *
 * @final
 */
class JsonRequestUtils
{
    private static PropertyAccessor|null $propertyAccessor = null;

    public static function isJsonRequest(Request $request): bool
    {
        return str_contains((string) $request->getContentTypeFormat(), 'json');
    }

    /**
     * Read data from a JSON payload.
     * Paths like foo.bar will be evaluated to find deeper items in nested data structures.
     */
    public static function getJsonPayloadValue(Request $request, string $parameterName): string|int|float|bool|null
    {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        $data = json_decode((string) $request->getContent());
        if (!$data instanceof stdClass) {
            throw new BadRequestException('Invalid JSON.');
        }

        if (null === self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        try {
            $value = self::$propertyAccessor->getValue($data, $parameterName);
        } catch (AccessException) {
            return null;
        }

        if (null !== $value && !is_scalar($value)) {
            throw new BadRequestException('Invalid JSON data, expected a scalar value.');
        }

        return $value;
    }
}
