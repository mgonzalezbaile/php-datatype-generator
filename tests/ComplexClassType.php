<?php

namespace Mgonzalezbaile\PdgTest;

use Mgonzalezbaile\Pdg\CustomClass\AttributeDefinition;
use Mgonzalezbaile\Pdg\CustomClass\ClassTypeDefinition;
use Mgonzalezbaile\Pdg\CustomClass\ConstDefinition;
use Mgonzalezbaile\Pdg\CustomClass\ConventionCustomClass;
use Mgonzalezbaile\Pdg\Parser\Constructor;

class ComplexClassType extends ConventionCustomClass
{
    public function implementClasses(): array
    {
        return [
            "Some\CustomNamespace\InterfaceClass",
            "Some\CustomNamespace\AnotherInterfaceClass",
        ];
    }

    public function classType(): string
    {
        return ClassTypeDefinition::TYPE_FINAL_CLASS;
    }

    public function extendClasses(): array
    {
        return [
            "Some\Other\CustomNamespace\ExtensibleClass",
        ];
    }

    public function publicConsts(): array
    {
        return [
            new ConstDefinition('someKey1', '"someValue1"'),
            new ConstDefinition('someKey2', '"someValue2"'),
        ];
    }

    public function privateConsts(): array
    {
        return [
            new ConstDefinition('someKey3', '5'),
            new ConstDefinition('someKey4', '10'),
        ];
    }

    public function protectedAttrs(): array
    {
        return [
            new AttributeDefinition('string', 'protectedAttr1', '"hello"'),
            new AttributeDefinition('string', 'protectedAttr2', null),
        ];
    }

    public function uses(): array
    {
        return ["Some\YetAnother\CustomNamespace\UsedClass"];
    }

    public function traits(): array
    {
        return [
            "Traits\SomeTrait",
            "Traits\AnotherTrait",
        ];
    }

    /**
     * @return AttributeDefinition[]
     */
    public function privateAttrs(): array
    {
        return [
            new AttributeDefinition('string', 'privateAttr1', '"hello"'),
            new AttributeDefinition('int', 'privateAttr2', '5'),
            new AttributeDefinition('int', 'privateAttr3', null),
        ];
    }

    public function assertCustomTypeIsValid(Constructor $constructor)
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
}
