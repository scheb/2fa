<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\ParameterBagUtils;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ParameterBagUtilsTest extends TestCase
{
    /**
     * @var MockObject|Request
     */
    private $request;

    protected function setUp(): void
    {
        $this->request = new Request([
            'topLevelField' => 'topLevelValue',
            'arrayArrayField' => ['nestedField' => 'nestedValue'],
        ]);
    }

    /**
     * @test
     */
    public function getRequestParameterValue_nonExistentField_returnNull(): void
    {
        $returnValue = ParameterBagUtils::getRequestParameterValue($this->request, 'nonExistentField');
        $this->assertNull($returnValue);
    }

    /**
     * @test
     */
    public function getRequestParameterValue_topLevelField_returnValue(): void
    {
        $returnValue = ParameterBagUtils::getRequestParameterValue($this->request, 'topLevelField');
        $this->assertEquals('topLevelValue', $returnValue);
    }

    /**
     * @test
     */
    public function getRequestParameterValue_arrayArrayField_returnValue(): void
    {
        $returnValue = ParameterBagUtils::getRequestParameterValue($this->request, 'arrayArrayField[nestedField]');
        $this->assertEquals('nestedValue', $returnValue);
    }
}
