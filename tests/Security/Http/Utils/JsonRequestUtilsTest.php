<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Utils;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\Utils\BadRequestException;
use Scheb\TwoFactorBundle\Security\Http\Utils\JsonRequestUtils;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class JsonRequestUtilsTest extends TestCase
{
    /**
     * @var MockObject|Request
     */
    private $request;

    protected function setUp(): void
    {
        $payload = json_encode([
            'topLevelField' => 'topLevelValue',
            'objectField' => ['nestedField' => 'nestedValue'],
        ]);
        $this->request = $this->createRequestWithPayload($payload);
    }

    private function createRequestWithPayload(string $payload): Request
    {
        return new Request([], [], [], [], [], [], $payload);
    }

    /**
     * @test
     */
    public function isJsonRequest_contentTypeContainsJson_returnTrue(): void
    {
        $request = new Request();
        $request->headers->set('CONTENT_TYPE', 'application/json');
        $this->assertTrue(JsonRequestUtils::isJsonRequest($request));
    }

    /**
     * @test
     */
    public function isJsonRequest_contentTypeNotJson_returnFalse(): void
    {
        $request = new Request();
        $request->headers->set('CONTENT_TYPE', 'text/plain');
        $this->assertFalse(JsonRequestUtils::isJsonRequest($request));
    }

    /**
     * @test
     */
    public function getJsonPayloadValue_invalidJsonPayload_throwBadRequestException(): void
    {
        $this->expectException(BadRequestException::class);

        $request = $this->createRequestWithPayload('');
        JsonRequestUtils::getJsonPayloadValue($request, 'nonExistentField');
    }

    /**
     * @test
     */
    public function getJsonPayloadValue_nonScalarValue_throwBadRequestException(): void
    {
        $this->expectException(BadRequestException::class);

        JsonRequestUtils::getJsonPayloadValue($this->request, 'objectField');
    }

    /**
     * @test
     */
    public function getJsonPayloadValue_nonExistentField_returnNull(): void
    {
        $returnValue = JsonRequestUtils::getJsonPayloadValue($this->request, 'nonExistentField');
        $this->assertNull($returnValue);
    }

    /**
     * @test
     */
    public function getJsonPayloadValue_topLevelField_returnValue(): void
    {
        $returnValue = JsonRequestUtils::getJsonPayloadValue($this->request, 'topLevelField');
        $this->assertEquals('topLevelValue', $returnValue);
    }

    /**
     * @test
     */
    public function getJsonPayloadValue_arrayArrayField_returnValue(): void
    {
        $returnValue = JsonRequestUtils::getJsonPayloadValue($this->request, 'objectField.nestedField');
        $this->assertEquals('nestedValue', $returnValue);
    }
}
