<?php

namespace Mgonzalezbaile\Pdg\CustomClass;

use Mgonzalezbaile\Pdg\Parser\Argument;

class AttributeDefinition extends Argument
{
    /**
     * @var null|string
     */
    private $defaultValue;

    public static function fromArgument(Argument $argument)
    {
        return new self($argument->name(), $argument->type(), $argument->nullable(), $argument->isList(), null);
    }

    public function __construct(string $name, string $type, bool $isNullable, bool $isList, ?string $defaultValue)
    {
        $this->defaultValue = $defaultValue;
        parent::__construct($name, $type, $isNullable, $isList);
    }

    public function defaultValue(): ?string
    {
        return $this->defaultValue;
    }
    public function hasDefaultValue(): bool
    {
        return $this->defaultValue !== null;
    }
}
