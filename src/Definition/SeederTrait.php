<?php

namespace Spartan\Db\Definition;

/**
 * SeederTrait
 *
 * @package Spartan\Db
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
trait SeederTrait
{
    protected array $options = [
        'class'  => null,
        'table'  => null,
        'locale' => 'en_US',
        'count'  => 5,
    ];

    public function __construct(array $options = [])
    {
        $this->options = $options + $this->options;
    }

    public function __invoke()
    {
        // do something here
    }
}
