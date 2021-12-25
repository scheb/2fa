<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Utils;

use Symfony\Component\HttpFoundation\Request;

/**
 * @final
 */
class RequestDataReader
{
    public function getRequestValue(Request $request, string $parameterName): string|int|float|bool|null
    {
        if (JsonRequestUtils::isJsonRequest($request)) {
            return JsonRequestUtils::getJsonPayloadValue($request, $parameterName);
        }

        return ParameterBagUtils::getRequestParameterValue($request, $parameterName);
    }
}
