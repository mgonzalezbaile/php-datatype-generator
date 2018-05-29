<?php
declare(strict_types=1);

namespace Mgonzalezbaile\PdgTest\Template\Output;

use \Some\YetAnother\CustomNamespace\UsedClass;
use \Some\CustomNamespace\InterfaceClass;
use \Some\CustomNamespace\AnotherInterfaceClass;
use \Some\Other\CustomNamespace\ExtensibleClass;
use \Traits\SomeTrait;
use \Traits\AnotherTrait;

final class ComplexClass extends ExtensibleClass implements InterfaceClass, AnotherInterfaceClass
{
    use SomeTrait;
    use AnotherTrait;
    public const someKey1 = "someValue1";
    public const someKey2 = "someValue2";
    private const someKey3 = 5;
    private const someKey4 = 10;

    /**
     * @var string
     */
    protected $protectedAttr1 = "hello";

    /**
     * @var string
     */
    protected $protectedAttr2;

    /**
     * @var string
     */
    private $privateAttr1 = "hello";

    /**
     * @var int
     */
    private $privateAttr2 = 5;

    /**
     * @var int
     */
    private $privateAttr3;

    /**
     * @var int|null
     */
    private $nullablePrivateAttr4;

    /**
     * @var string[]
     */
    private $listPrivateAttr5;

    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $otherThing;

    /**
     * @var int|null
     */
    private $nullable;

    /**
     * @var string[]
     */
    private $lastThing;

    public function __construct(string $id, int $otherThing, ?int $nullable, array $lastThing)
    {
        $this->id         = $id;
        $this->otherThing = $otherThing;
        $this->nullable   = $nullable;
        $this->lastThing  = $lastThing;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function otherThing(): int
    {
        return $this->otherThing;
    }

    public function nullable(): ?int
    {
        return $this->nullable;
    }

    public function lastThing(): array
    {
        return $this->lastThing;
    }

    public function privateAttr1(): string
    {
        return $this->privateAttr1;
    }

    public function privateAttr2(): int
    {
        return $this->privateAttr2;
    }

    public function privateAttr3(): int
    {
        return $this->privateAttr3;
    }

    public function nullablePrivateAttr4(): ?int
    {
        return $this->nullablePrivateAttr4;
    }

    public function listPrivateAttr5(): array
    {
        return $this->listPrivateAttr5;
    }

    public function withId(string $id): self
    {
        $new      = clone $this;
        $this->id = $id;

        return $new;
    }

    public function withOtherThing(int $otherThing): self
    {
        $new              = clone $this;
        $this->otherThing = $otherThing;

        return $new;
    }

    public function withNullable(?int $nullable): self
    {
        $new            = clone $this;
        $this->nullable = $nullable;

        return $new;
    }

    public function withLastThing(array $lastThing): self
    {
        $new             = clone $this;
        $this->lastThing = $lastThing;

        return $new;
    }

    public function withPrivateAttr1(string $privateAttr1): self
    {
        $new                = clone $this;
        $this->privateAttr1 = $privateAttr1;

        return $new;
    }

    public function withPrivateAttr2(int $privateAttr2): self
    {
        $new                = clone $this;
        $this->privateAttr2 = $privateAttr2;

        return $new;
    }

    public function withPrivateAttr3(int $privateAttr3): self
    {
        $new                = clone $this;
        $this->privateAttr3 = $privateAttr3;

        return $new;
    }

    public function withNullablePrivateAttr4(?int $nullablePrivateAttr4): self
    {
        $new                        = clone $this;
        $this->nullablePrivateAttr4 = $nullablePrivateAttr4;

        return $new;
    }

    public function withListPrivateAttr5(array $listPrivateAttr5): self
    {
        $new                    = clone $this;
        $this->listPrivateAttr5 = $listPrivateAttr5;

        return $new;
    }
}
