<?php namespace Illuminate\Validation;

class MessageContainer {

	/**
	 * All of the messages.
	 *
	 * @var array
	 */
	protected $messages;

	/**
	 * The global format of the messages.
	 *
	 * @return string
	 */
	protected $format;

	/**
	 * Create a new message container instance.
	 *
	 * @param  array   $messages
	 * @param  string  $format
	 * @return void
	 */
	public function __construct(array $messages = array(), $format = ':message')
	{
		$this->format = $format;
		$this->messages = $messages;
	}

}