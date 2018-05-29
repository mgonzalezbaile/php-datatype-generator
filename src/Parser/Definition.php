<?php
/**
 * This file is part of prolic/fpp.
 * (c) 2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Mgonzalezbaile\Pdg\Parser;

use Mgonzalezbaile\Pdg\Condition;
use Mgonzalezbaile\Pdg\CustomClass\ConventionCustomClass;
use Mgonzalezbaile\Pdg\Deriving;

class Definition
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Constructor
     */
    private $constructor;

    /**
     * @var string
     */
    private $deriving;

    /**
     * @var Condition[]
     */
    private $conditions = [];

    /**
     * @var string|null
     */
    private $messageName;

    /**
     * @param string        $namespace
     * @param string        $className
     * @param Constructor[] $constructors
     * @param Deriving[]    $derivings
     * @param Condition[]   $conditions
     * @param string|null   $messageName
     */
    public function __construct(
        string $namespace,
        string $className,
        array $constructors = [],
        array $derivings = [],
        array $conditions = [],
        string $messageName = null
    ) {
        if (count($derivings)>1) {
            throw new \InvalidArgumentException("Multiple derivings not supported");
        }
        if (count($constructors)>1) {
            throw new \InvalidArgumentException("Multiple constructors not supported");
        }

        $this->deriving = $derivings[0];
        $this->constructor = $constructors[0];


        $this->namespace = $namespace;
        $this->name = $className;
    }

    public function generateFrom(string $generatorsNamespace): string
    {
        $customClassPath = $generatorsNamespace . "\\" . $this->deriving;
        /** @var ConventionCustomClass $class */
        $class = new $customClassPath;

        return $class->generate($this->namespace, $this->name, $this->constructor);
    }

    /**
     * @return string
     */
    public function namespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Constructor
     */
    public function constructor(): Constructor
    {
        return $this->constructor;
    }

    /**
     * @return string
     */
    public function deriving(): string
    {
        return $this->deriving;
    }

    /**
     * @return Condition[]
     */
    public function conditions(): array
    {
        return $this->conditions;
    }

    public function messageName(): ?string
    {
        return $this->messageName;
    }

    private function invalid(string $message): \InvalidArgumentException
    {
        return new \InvalidArgumentException(sprintf(
            'Error on %s%s: %s',
            $this->namespace . '\\',
            $this->name,
            $message
        ));
    }
}
