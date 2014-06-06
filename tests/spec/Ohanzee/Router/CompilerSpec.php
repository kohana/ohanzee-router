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

	function it_converts_uri_strings_into_matching_regexes()
	{
		$this->compile('foo/<bar>')->shouldMatchUri('foo/bar');
	}

	function it_changes_the_variable_scope_based_on_regex()
	{
		$this->compile('foo/<bar>', array('bar' => '\d'))->shouldNotMatchUri('foo/bar');
	}

	function it_makes_parts_of_the_uri_optional()
	{
		$this->compile('foo/<bar>(/<foo>)')->shouldMatchUri('foo/bar');
	}

	public function getMatchers()
	{
		return [
			'matchUri' => function($subject, $uri) {
				return preg_match($subject, $uri);
			},
			'notMatchUri' => function($subject, $uri) {
				return (preg_match($subject, $uri) === 0);
			},
		];
	}
}
