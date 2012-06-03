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
		$this->assertEquals(array('foo' => 'bar'), $validator->getData());
		$this->assertEquals(array('baz' => array('boom')), $validator->getRules());

		$presence = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$factory->addExtension('foo', function() {});
		$factory->setPresenceVerifier($presence);
		$validator = $factory->make(array(), array());
		$this->assertEquals(array('foo' => function() {}), $validator->getExtensions());
		$this->assertEquals($presence, $validator->getPresenceVerifier());
	}

}