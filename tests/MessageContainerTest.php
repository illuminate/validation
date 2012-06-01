<?php

use Illuminate\Validation\MessageContainer;

class MessageContainerTest extends PHPUnit_Framework_TestCase {

	public function testUniqueness()
	{
		$container = new MessageContainer;
		$container->add('foo', 'bar');
		$container->add('foo', 'bar');
		$messages = $container->getMessages();
		$this->assertEquals(array('bar'), $messages['foo']);
	}

}