<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Utils;

use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\Http\Utils\BadRequestException;
use Scheb\TwoFactorBundle\Security\Http\Utils\RequestDataReader;
use Symfony\Component\HttpFoundation\Request;

class RequestDataReaderTest extends TestCase
{
    /**
     * @var RequestDataReader
     */
    private $requestDataReader;

    protected function setUp(): void
    {
        $this->requestDataReader = new RequestDataReader();
    }

    private function createPostDataRequest(array $postData): Request
    {
        return new Request([], $postData);
    }

    private function createJsonRequest(string $postData): Request
    {
        $request = new Request([], [], [], [], [], [], $postData);
        $request->headers->set('CONTENT_TYPE', 'application/json');

        return $request;
    }

    /**
     * @test
     */
    public function getRequestValue_hasPostParameterSet_returnValue(): void
    {
        $postData = ['param' => 'paramValue'];
        $request = $this->createPostDataRequest($postData);

        $returnValue = $this->requestDataReader->getRequestValue($request, 'param');
        $this->assertEquals('paramValue', $returnValue);
    }

    /**
     * @test
     */
    public function getRequestValue_nestedPostParameterSet_returnValue(): void
    {
        $postData = ['array_object' => ['param' => 'paramValue']];
        $request = new Request([], $postData);

        $returnValue = $this->requestDataReader->getRequestValue($request, 'array_object[param]');
        $this->assertEquals('paramValue', $returnValue);
    }

    /**
     * @test
     */
    public function getRequestValue_postParameterNotSet_returnNull(): void
    {
        $request = new Request([], []);

        $returnValue = $this->requestDataReader->getRequestValue($request, 'param');
        $this->assertNull($returnValue);
    }

    /**
     * @test
     */
    public function getRequestValue_hasJsonParameterSet_returnValue(): void
    {
        $postData = '{"param":"paramValue"}';
        $request = $this->createJsonRequest($postData);

        $returnValue = $this->requestDataReader->getRequestValue($request, 'param');
        $this->assertEquals('paramValue', $returnValue);
    }

    /**
     * @test
     */
    public function getRequestValue_nestedJsonParameterSet_returnValue(): void
    {
        $postData = '{"object":{"param":"paramValue"}}';
        $request = $this->createJsonRequest($postData);

        $returnValue = $this->requestDataReader->getRequestValue($request, 'object.param');
        $this->assertEquals('paramValue', $returnValue);
    }

    /**
     * @test
     */
    public function getRequestValue_jsonParameterNotSet_returnNull(): void
    {
        $postData = '{}';
        $request = $this->createJsonRequest($postData);

        $returnValue = $this->requestDataReader->getRequestValue($request, 'param');
        $this->assertNull($returnValue);
    }

    /**
     * @test
     */
    public function getRequestValue_invalidJsonPayload_throwBadRequestException(): void
    {
        $postData = '{';
        $request = $this->createJsonRequest($postData);

        $this->expectException(BadRequestException::class);

        $this->requestDataReader->getRequestValue($request, 'param');
    }

    /**
     * @test
     */
    public function getRequestValue_missingJsonPayload_throwBadRequestException(): void
    {
        $postData = '';
        $request = $this->createJsonRequest($postData);

        $this->expectException(BadRequestException::class);

        $this->requestDataReader->getRequestValue($request, 'param');
    }
}
