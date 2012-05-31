<?php

use Mockery as m;
use Illuminate\Validation\Factory;

class FactoryTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMakeMethodCreatesValidValidator()
	{
		$translator = m::mock('Symfony\Component\Translation\TranslatorInterface');
		$factory = new Factory($translator);
		$validator = $factory->make(array('foo' => 'bar'), array('baz' => 'boom'));
		$this->assertEquals($translator, $validator->getTranslator());
		$this->assertNull($validator->getPresenceVerifier());
		$this->assertEquals(array('foo' => 'bar'), $validator->getData());
		$this->assertEquals(array('baz' => 'boom'), $validator->getRules());
	}

}