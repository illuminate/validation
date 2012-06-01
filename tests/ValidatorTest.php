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
		$v = new Validator($trans, array('foo' => 'taylor'), array('name' => 'Confirmed'));
		$this->assertTrue($v->passes());
	}


	public function testProperLanguageLineIsSet()
	{
		$trans = $this->getRealTranslator();
		$trans->addResource('array', array('validation.required' => 'required!'), 'en', 'messages');
		$v = new Validator($trans, array('name' => ''), array('name' => 'Required'));
		$this->assertFalse($v->passes());
		$this->assertEquals('required!', $v->errors->first('name'));
	}


	public function testAttributeNamesAreReplaced()
	{
		$trans = $this->getRealTranslator();
		$trans->addResource('array', array('validation.required' => ':attribute is required!'), 'en', 'messages');
		$v = new Validator($trans, array('name' => ''), array('name' => 'Required'));
		$this->assertFalse($v->passes());
		$this->assertEquals('name is required!', $v->errors->first('name'));
	}


	public function testCustomValidationLinesAreRespected()
	{
		$trans = $this->getRealTranslator();
		$trans->addResource('array', array('validation.required' => 'required!', 'validation.name.required' => 'really required!'), 'en', 'messages');
		$v = new Validator($trans, array('name' => ''), array('name' => 'Required'));
		$this->assertFalse($v->passes());
		$this->assertEquals('really required!', $v->errors->first('name'));
	}


	protected function getTranslator()
	{
		return m::mock('Symfony\Component\Translation\TranslatorInterface');
	}


	protected function getRealTranslator()
	{
		$trans = new Symfony\Component\Translation\Translator('en');
		$trans->addLoader('array', new Symfony\Component\Translation\Loader\ArrayLoader);
		return $trans;
	}

}