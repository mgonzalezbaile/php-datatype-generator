<?php

namespace Mgonzalezbaile\Pdg\CustomClass;

use Mgonzalezbaile\Pdg\Parser\Argument;
use Mgonzalezbaile\Pdg\Parser\Constructor;

abstract class ConventionCustomClass
{
    /**
     * @return UseDefinition[]
     */
    protected function uses(): array
    {
        return [];
    }

    abstract protected function classType(): string;

    /**
     * @return string[]
     */
    protected function extendClasses(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    protected function implementClasses(): array
    {
        return [];
    }

    /**
     * @return ConstDefinition[]
     */
    protected function publicConsts(): array
    {
        return [];
    }

    /**
     * @return ConstDefinition[]
     */
    protected function privateConsts(): array
    {
        return [];
    }

    /**
     * @return AttributeDefinition[]
     */
    protected function protectedAttrs(): array
    {
        return [];
    }

    /**
     * @return AttributeDefinition[]
     */
    protected function privateAttrs(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    protected function traits(): array
    {
        return [];
    }

    protected function assertCustomTypeIsValid(Constructor $constructor)
    {
    }

    public function generate(string $namespace, string $className, Constructor $constructor): string
    {
        $text = <<<CODE
        <?php
        declare(strict_types=1);
        namespace $namespace;
CODE;


        $text = $this->useSection($text);
        $text .= "\n";
        $text = $this->classDefinitionSection($className, $text);
        $text .= "\n{\n"; //OPEN CLASS BODY
        $text = $this->traitsSection($text);
        $text = $this->constantsSection($text);
        $text = $this->attributesSection($constructor, $text);
        $text = $this->constructorSection($constructor, $text);
        $text = $this->accessorsSection($constructor, $text);
        $text = $this->settersSection($constructor, $text);
        $text .= "}\n"; //CLOSE CLASS BODY

        $this->assertCustomTypeIsValid($constructor);

        return $text;
    }

    private function useSection(string $text): string
    {
        foreach ($this->uses() as $item) {
            $text .= "use \\" . $item . ";\n";
        }

        foreach ($this->implementClasses() as $item) {
            $text .= "use \\" . $item . ";\n";
        }

        foreach ($this->extendClasses() as $item) {
            $text .= "use \\" . $item . ";\n";
        }

        foreach ($this->traits() as $item) {
            $text .= "use \\" . $item . ";\n";
        }

        return $text;
    }

    private function traitsSection(string $text): string
    {
        foreach ($this->traits() as $trait) {
            $traitClassName = explode("\\", $trait);
            $traitClassName = end($traitClassName);
            $text           .= "use " . $traitClassName . ";\n";
        }

        return $text;
    }

    private function constantsSection(string $text): string
    {
        foreach ($this->publicConsts() as $const) {
            $text .= "public const " . $const->key() . " = " . $const->value() . ";";
        }

        foreach ($this->privateConsts() as $const) {
            $text .= "private const " . $const->key() . " = " . $const->value() . ";";
        }

        return $text;
    }

    private function attributesSection(Constructor $constructor, string $text): string
    {
        foreach ($this->protectedAttrs() as $protectedAttr) {
            $text = $this->buildAttribute($text, $protectedAttr, 'protected');
        }

        foreach ($this->privateAttrs() as $privateAttr) {
            $text = $this->buildAttribute($text, $privateAttr, 'private');
        }

        //PRIVATE ATTRIBUTES FROM CONSTRUCTOR
        foreach ($constructor->arguments() as $argument) {
            $text = $this->buildAttribute($text, AttributeDefinition::fromArgument($argument), 'private');
        }

        return $text;
    }

    private function buildAttribute(string $text, AttributeDefinition $argument, string $attributeType): string
    {
        $text .= "/**\n* @var ";
        if ($argument->isList()) {
            $text .= $argument->type() . "[]";
        } else {
            $text .= $argument->type();
        }
        if ($argument->nullable()) {
            $text .= "|null";
        }
        $text .= "\n*/\n$attributeType \$" . $argument->name();

        if ($argument->hasDefaultValue()) {
            $text .= " = " . $argument->defaultValue();
        }

        $text .= ";\n";

        return $text;
    }

    private function classDefinitionSection(string $className, string $text): string
    {
        $text .= $this->classType() . ' ' . $className;

        if (!empty($this->extendClasses())) {
            $text     .= ' extends ';
            $lastItem = end($this->extendClasses());
            foreach ($this->extendClasses() as $implementClass) {
                $implementClassName = explode("\\", $implementClass);
                $text               .= end($implementClassName);
                if ($implementClass !== $lastItem) {
                    $text .= ', ';
                }
            }
        }

        if (!empty($this->implementClasses())) {
            $text     .= ' implements ';
            $lastItem = end($this->implementClasses());
            foreach ($this->implementClasses() as $implementClass) {
                $implementClassName = explode("\\", $implementClass);
                $text               .= end($implementClassName);
                if ($implementClass !== $lastItem) {
                    $text .= ', ';
                }
            }
        }

        return $text;
    }

    private function constructorSection(Constructor $constructor, string $text): string
    {
        $text .= "public function __construct(";

        $lastArgument = end($constructor->arguments());
        foreach ($constructor->arguments() as $argument) {
            if ($argument->nullable()) {
                $text .= "?";
            }
            if ($argument->isList()) {
                $text .= "array";
            } else {
                $text .= $argument->type();
            }
            $text .= " $" . $argument->name();
            if ($argument !== $lastArgument) {
                $text .= ', ';
            }
        }

        $text .= ")\n{\n"; //OPEN CONSTRUCTOR BODY

        foreach ($constructor->arguments() as $argument) {
            $text .= "\$this->" . $argument->name() . " = $" . $argument->name() . ";\n";
        }

        $text .= "}";

        return $text; //CLOSE CONSTRUCTOR BODY
    }

    private function accessorsSection(Constructor $constructor, string $text): string
    {
        foreach ($constructor->arguments() as $argument) {
            $text = $this->buildAccessor($text, $argument);
        }

        foreach ($this->privateAttrs() as $privateAttr) {
            $text = $this->buildAccessor($text, $privateAttr);
        }

        return $text;
    }

    private function buildAccessor(string $text, Argument $argument): string
    {
        $text .= "public function " . $argument->name() . "(): ";
        if ($argument->nullable()) {
            $text .= "?";
        }
        if ($argument->isList()) {
            $text .= "array";
        } else {
            $text .= $argument->type();
        }
        $text .= "\n{";
        $text .= "return \$this->" . $argument->name() . ";\n";
        $text .= "}\n";

        return $text;
    }

    private function settersSection(Constructor $constructor, string $text): string
    {
        foreach ($constructor->arguments() as $argument) {
            $text = $this->buildSetter($text, $argument);
        }

        foreach ($this->privateAttrs() as $privateAttr) {
            $text = $this->buildSetter($text, $privateAttr);
        }

        return $text;
    }

    private function buildSetter(string $text, Argument $argument): string
    {
        $text .= "public function with" . ucfirst($argument->name()) . "(";
        if ($argument->nullable()) {
            $text .= "?";
        }
        if ($argument->isList()) {
            $text .= "array";
        } else {
            $text .= $argument->type();
        }
        $text .= " $" . $argument->name() . "): self\n{";
        $text .= "\$new = clone \$this;";
        $text .= "\$this->" . $argument->name() . " = $" . $argument->name() . ";\n";
        $text .= "return \$new;\n}\n";

        return $text;
    }
}
