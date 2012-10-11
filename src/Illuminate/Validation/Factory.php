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
	 * The Validator resolver instance.
	 *
	 * @var Closure
	 */
	protected $resolver;

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
		$validator = $this->resolve($data, $rules);

		if ( ! is_null($this->presenceVerifier))
		{
			$validator->setPresenceVerifier($this->presenceVerifier);
		}

		$validator->addExtensions($this->extensions);

		return $validator;
	}

	/**
	 * Resolve a new Validator instance.
	 *
	 * @param  array  $data
	 * @param  array  $rules
	 * @return Illuminate\Validation\Validator
	 */
	protected function resolve($data, $rules)
	{
		if (is_null($this->resolver))
		{
			return new Validator($this->translator, $data, $rules);
		}
		else
		{
			return call_user_func($this->resolver, $this->translator, $data, $rules);
		}
	}

	/**
	 * Register a custom validator extension.
	 *
	 * @param  string  $rule
	 * @param  Closure  $extension
	 * @return void
	 */
	public function extend($rule, Closure $extension)
	{
		$this->extensions[$rule] = $extension;
	}

	/**
	 * Set the Validator instance resolver.
	 *
	 * @param  Closure  $resolver
	 * @return void
	 */
	public function resolver(Closure $resolver)
	{
		$this->resolver = $resolver;
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