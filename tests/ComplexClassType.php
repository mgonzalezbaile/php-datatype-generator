<?php

namespace Mgonzalezbaile\PdgTest;

use Mgonzalezbaile\Pdg\CustomClass\AttributeDefinition;
use Mgonzalezbaile\Pdg\CustomClass\ClassTypeDefinition;
use Mgonzalezbaile\Pdg\CustomClass\ConstDefinition;
use Mgonzalezbaile\Pdg\CustomClass\ConventionCustomClass;
use Mgonzalezbaile\Pdg\Parser\Constructor;

class ComplexClassType extends ConventionCustomClass
{
    protected function implementClasses(): array
    {
        return [
            "Some\CustomNamespace\InterfaceClass",
            "Some\CustomNamespace\AnotherInterfaceClass",
        ];
    }

    protected function classType(): string
    {
        return ClassTypeDefinition::TYPE_FINAL_CLASS;
    }

    protected function extendClasses(): array
    {
        return [
            "Some\Other\CustomNamespace\ExtensibleClass",
        ];
    }

    protected function publicConsts(): array
    {
        return [
            new ConstDefinition('someKey1', '"someValue1"'),
            new ConstDefinition('someKey2', '"someValue2"'),
        ];
    }

    protected function privateConsts(): array
    {
        return [
            new ConstDefinition('someKey3', '5'),
            new ConstDefinition('someKey4', '10'),
        ];
    }

    protected function protectedAttrs(): array
    {
        return [
            new AttributeDefinition('protectedAttr1', 'string', false, false, '"hello"'),
            new AttributeDefinition('protectedAttr2', 'string', false, false, null),
        ];
    }

    protected function uses(): array
    {
        return ["Some\YetAnother\CustomNamespace\UsedClass"];
    }

    protected function useFunctions(): array
    {
        return [
            "Some\CustomNamespace\myfunction",
        ];
    }

    protected function traits(): array
    {
        return [
            "Traits\SomeTrait",
            "Traits\AnotherTrait",
        ];
    }

    /**
     * @return AttributeDefinition[]
     */
    protected function privateAttrs(): array
    {
        return [
            new AttributeDefinition('privateAttr1', 'string', false, false, '"hello"'),
            new AttributeDefinition('privateAttr2', 'int', false, false, '5'),
            new AttributeDefinition('privateAttr3', 'int', false, false, null),
            new AttributeDefinition('nullablePrivateAttr4', 'int', true, false, null),
            new AttributeDefinition('listPrivateAttr5', 'string', false, true, null),
        ];
    }

    protected function assertCustomTypeIsValid(Constructor $constructor)
    {
        $idFound = false;
        $count   = 0;
        while (!$idFound && $count < count($constructor->arguments())) {
            if ($constructor->arguments()[$count]->name() === 'id') {
                $idFound = true;
            }

            $count++;
        }

        if (!$idFound) {
            throw new \Exception("'string \$id' should be provided for ComplexClassType as convention");
        }
    }

    protected function customMethods(): array
    {
        return [
            $this->buildMethod1(),
            $this->buildMethod2(),
        ];
    }

    private function buildMethod1(): string
    {
        return <<<CODE
    public function someCustomMethod(string \$param1): string
    {
        return \$param1;
    }
CODE;
    }

    private function buildMethod2(): string
    {
        return <<<CODE
    public function someOtherCustomMethod(): string
    {
        return \$this->id;
    }
CODE;
    }
}
