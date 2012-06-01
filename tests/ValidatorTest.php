<?php

use Mockery as m;
use Illuminate\Validation\Validator;

class ValidatorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testInValidatableRulesReturnsValid()
	{
		$trans = $this->getTranslator();
		$trans->shouldReceive('trans')->never();
		$v = new Validator($trans, array('foo' => 'taylor'), array('name' => 'confirmed'));
		$this->assertTrue($v->passes());
	}


	public function getTranslator()
	{
		return m::mock('Symfony\Component\Translation\TranslatorInterface');
	}

}