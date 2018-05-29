<?php

namespace Mgonzalezbaile\Pdg\CustomClass;

class AttributeDefinition
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var null|string
     */
    private $defaultValue;

    public function __construct(string $type, string $name, ?string $defaultValue)
    {
        $this->type         = $type;
        $this->name         = $name;
        $this->defaultValue = $defaultValue;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
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
