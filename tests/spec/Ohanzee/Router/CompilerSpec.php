<?php

namespace spec\Ohanzee\Router;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CompilerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Ohanzee\Router\Compiler');
    }
}
