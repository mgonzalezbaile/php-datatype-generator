<?php
/**
 * Created by PhpStorm.
 * User: mikel
 * Date: 25/05/18
 * Time: 10:34
 */

namespace Mgonzalezbaile\Pdg\CustomClass;

class UseDefinition
{
    /**
     * @var string
     */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
