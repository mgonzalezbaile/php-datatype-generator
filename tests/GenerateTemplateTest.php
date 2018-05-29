<?php

namespace Mgonzalezbaile\PdgTest;

use PHPUnit\Framework\TestCase;

class GenerateTemplateTest extends TestCase
{
    /**
     * @var string
     */
    private $rootPathDatatypeFile;

    /**
     * @var string
     */
    private $namespaceGeneratorClass;

    public function setUp()
    {
        $this->rootPathDatatypeFile    = __DIR__ . "/Template/Input/";
        $this->namespaceGeneratorClass = "Mgonzalezbaile\\PdgTest";
    }

    public function tearDown()
    {
        $outputPath = __DIR__ . "/Template/Output/ComplexClass.php";
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    /**
     * @test
     */
    public function shouldGenerateTemplate()
    {
        $datatypePath = $this->rootPathDatatypeFile . "success-sample.datatypes";
        exec("php bin/pdg $datatypePath \"$this->namespaceGeneratorClass\"");
        $result         = file_get_contents(__DIR__ . "/Template/Output/ComplexClass.php");
        $expectedResult = file_get_contents(__DIR__ . "/Template/Expected/ComplexClass.php");

        $this->assertEquals($this->removeWhitespaces($expectedResult), $this->removeWhitespaces($result));
    }

    private function removeWhitespaces($result): string
    {
        return preg_replace('/\s+/', '', $result);
    }

    /**
     * @test Missing $id param in constructor required by ComplexClassType
     */
    public function shouldFail_If_BadConstructorGivenAccordingToCustomType()
    {
        $datatypePath = $this->rootPathDatatypeFile . "wrong-sample.datatypes";
        $result       = exec("php bin/pdg $datatypePath $this->namespaceGeneratorClass");

        $this->assertNotEquals("Successfully generated and written to disk", $result);
    }
}
