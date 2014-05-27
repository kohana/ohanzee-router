<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RouteSpec extends ObjectBehavior
{
	function it_is_initializable()
	{
		$this->shouldHaveType('Ohanzee/Router/Route');
	}
}
