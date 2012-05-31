<?php namespace Illuminate\Validation;

class Validator {

	/**
	 * The Translator implementation.
	 *
	 * @var Symfony\Component\Translation\TranslatorInterface
	 */
	protected $translator;

	/**
	 * The data under validation.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The files under validation.
	 *
	 * @var array
	 */
	protected $files;

	/**
	 * The rules to be applied to the data.
	 *
	 * @var array
	 */
	protected $rules;

	/**
	 * Create a new Validator instance.
	 *
	 * @param  Symfony\Component\Translation\TranslatorInterface  $translator
	 * @param  array  $data
	 * @param  array  $rules
	 * @return void
	 */
	public function __construct(TranslatorInterface $translator, array $data, array $rules)
	{
		$this->data = $data;
		$this->rules = $rules;
		$this->translator = $translator;
	}

	/**
	 * Get the files under validation.
	 *
	 * @return array
	 */
	public function getFiles()
	{
		return $this->files;
	}

	/**
	 * Set the files under validation.
	 *
	 * @param  array  $files
	 * @return void
	 */
	public function setFiles(array $files)
	{
		$this->files = $files;
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
	 * Set the Translator implementation.
	 *
	 * @param Symfony\Component\Translation\TranslatorInterface  $translator
	 * @return void
	 */
	public function setTranslator(TranslatorInterface $translator)
	{
		$this->translator = $translator;
	}

}