<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Utils;

use Symfony\Component\HttpFoundation\Request;

/**
 * @final
 */
class RequestDataReader
{
    /**
     * @return scalar|null
     */
    public function getRequestValue(Request $request, string $parameterName)
    {
        if (JsonRequestUtils::isJsonRequest($request)) {
            return JsonRequestUtils::getJsonPayloadValue($request, $parameterName);
        }

        return ParameterBagUtils::getRequestParameterValue($request, $parameterName);
    }
}
