<?php namespace Illuminate\Validation;

use Closure;
use Symfony\Component\Translation\TranslatorInterface;

class Factory {

	/**
	 * The Translator implementation.
	 *
	 * @var Symfony\Component\Translator\TranslatorInterface
	 */
	protected $translator;

	/**
	 * The Presence Verifier implementation.
	 *
	 * @var Illuminate\Validation\PresenceVerifierInterface
	 */
	protected $presenceVerifier;

	/**
	 * All of the custom validator extensions.
	 *
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * Create a new Validator factory instance.
	 *
	 * @param  Symfony\Component\Translation\TranslatorInterface  $translator
	 * @return void
	 */
	public function __construct(TranslatorInterface $translator)
	{
		$this->translator = $translator;
	}

	/**
	 * Create a new Validator instance.
	 *
	 * @param  array  $data
	 * @param  array  $rules
	 * @return Illuminate\Validation\Validator
	 */
	public function make(array $data, array $rules)
	{
		$validator = new Validator($this->translator, $data, $rules);

		if ( ! is_null($this->presenceVerifier))
		{
			$validator->setPresenceVerifier($this->presenceVerifier);
		}

		$validator->addExtensions($this->extensions);

		return $validator;
	}

	/**
	 * Register a custom validator extension.
	 *
	 * @param  string  $rule
	 * @param  Closure  $extension
	 * @return void
	 */
	public function addExtension($rule, Closure $extension)
	{
		$this->extensions[$rule] = $extension;
	}

	/**
	 * Get the Translator implementation.
	 *
	 * @return Symfony\Component\Translation\TranslatorInterface
	 */
	public function getTranslator()
	{
		return $this->translator;
	}

	/**
	 * Get the Presence Verifier implementation.
	 *
	 * @return Illuminate\Validation\PresenceVerifierInterface
	 */
	public function getPresenceVerifier()
	{
		return $this->presenceVerifier;
	}

	/**
	 * Set the Presence Verifier implementation.
	 *
	 * @param  Illuminate\Validation\PresenceVerifierInterface  $presenceVerifier
	 * @return void
	 */
	public function setPresenceVerifier(PresenceVerifierInterface $presenceVerifier)
	{
		$this->presenceVerifier = $presenceVerifier;
	}

}