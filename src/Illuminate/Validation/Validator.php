<?php namespace Illuminate\Validation;

use Symfony\Component\Translation\TranslatorInterface;

class Validator {

	/**
	 * The Translator implementation.
	 *
	 * @var Symfony\Component\Translation\TranslatorInterface
	 */
	protected $translator;

	/**
	 * The Presence Verifier implementation.
	 *
	 * @var Illuminate\Validation\PresenceVerifierInterface
	 */
	protected $presenceVerifier;

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
	 * The size related validation rules.
	 *
	 * @var array
	 */
	protected $sizeRules = array('Size', 'Between', 'Min', 'Max');

	/**
	 * The numeric related validation rules.
	 *
	 * @var array
	 */
	protected $numericRules = array('Numeric', 'Integer');

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
	 * Determine if the data passes the validation rules.
	 *
	 * @return bool
	 */
	public function passes()
	{
		$this->errors = new MessageContainer;

		// We'll spin through each rule, validating the attributes attached to
		// that rule. Any error messages will be added to the container with
		// all of the other error messages, and return true if we get them.
		foreach ($this->rules as $attribute => $rule)
		{
			foreach ($this->rules as $rule)
			{
				$this->validate($attribute, $rule);
			}
		}

		return count($this->errors->all()) === 0;
	}

	/**
	 * Determine if the data fails the validation rules.
	 *
	 * @return bool
	 */
	public function fails()
	{
		return ! $this->passes();
	}

	/**
	 * Validate a given attribute against a rule.
	 *
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @return void
	 */
	protected function validate($attribute, $rule)
	{
		list($rule, $parameters) = $this->parseRule($rule);

		// We will get the value for the given attribute from the array of data and then
		// verify that the attribute is indeed validatable. Unless the rule implies
		// that the attribute is required, rules are not run for missing values.
		$value = $this->getValue($this->data, $attribute);

		$validatable = $this->isValidatable($rule, $attribute, $value);

		$method = "validate{$rule}";

		if ($validatable and ! $this->$method($attribute, $value, $parameters, $this))
		{
			$this->addError($attribute, $rule, $parameters);
		}
	}

	/**
	 * Get the value of a given attribute.
	 *
	 * @param  string  $attribute
	 * @return mixed
	 */
	protected function getValue($attribute)
	{
		if (array_key_exists($attribute, $this->data))
		{
			return $this->data[$attribute];
		}
	}

	/**
	 * Determine if the attribute is validatable.
	 *
	 * @param  string  $rule
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function isValidatable($rule, $attribute, $value)
	{
		return $this->validateRequired($attribute, $value) or $this->isImplicit($rule);
	}

	/**
	 * Determine if a given rule implies the attribute is required.
	 *
	 * @param  string  $rule
	 * @return bool
	 */
	protected function isImplicit($rule)
	{
		return $rule == 'Required' or $rule == 'Accepted';
	}

	/**
	 * Add an error message to the validator's collection of messages.
	 *
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return void
	 */
	protected function addError($attribute, $rule, $parameters)
	{
		$message = $this->getMessage($attribute, $rule);

		$message = $this->doReplacements($message, $attribute, $rule, $parameters);

		$this->errors->add($attribute, $message);
	}

	/**
	 * Validate that a required attribute exists.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateRequired($attribute, $value)
	{
		if (is_null($value))
		{
			return false;
		}
		elseif (is_string($value) and trim($value) === '')
		{
			return false;
		}
		elseif ( ! is_null($this->files) and isset($this->files[$attribute]))
		{
			return $this->files[$attribute]->getPath() !== '';
		}

		return true;
	}

	/**
	 * Validate that an attribute has a matching confirmation.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateConfirmed($attribute, $value)
	{
		return $this->validateSame($attribute, $value, array($attribute.'_confirmation'));
	}

	/**
	 * Validate that two attributes match.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return void
	 */
	protected function validateSame($attribute, $value, $parameters)
	{
		$other = $parameters[0];

		return isset($this->data[$other]) and $value == $this->data[$other];
	}

	/**
	 * Get the validation message for an attribute and rule.
	 *
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @return string
	 */
	protected function getMessage($attribute, $rule)
	{
		$lowerRule = strtolower($rule);

		$customKey = "validation.custom.{$attribute}.{$lowerRule}";

		$customMessage = $this->translator->trans($customKey);

		// First we check for a custom defined validation message for the attribute
		// and rule. This allows the developer to specify specific messages for
		// only some attributes and rules that need to be specially formed.
		if ($customMessage !== $customKey)
		{
			return $customMessage;
		}

		// If the rule being validated is a "size" rule, we will need to gather the
		// specific error message for the type of attribute being validated such
		// as a number, file or string which all have different message types.
		elseif (in_array($rule, $this->sizeRules))
		{
			return $this->getSizeMessage($attribute, $rule);
		}

		// Finally, if on developer specified messages have been set, and no other
		// special messages apply to this rule, we will just pull the default
		// message out of the translator service for this validation rule.
		else
		{
			$key = "validation.{$lowerRule}";

			return $this->translator->trans($key);
		}
	}

	/**
	 * Get the proper error message for an attribute and size rule.
	 *
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @return string
	 */
	protected function getSizeMessage($attribute, $rule)
	{
		$lowerRule = strtolower($rule);

		// There are three different types of size validations. The attribute may
		// be either a number, file, or string, so we will check a few things
		// to figure out which one it is and return the appropriate line.
		if ($this->hasRule($attribute, $this->numericRules))
		{
			$type = 'numeric';
		}

		// We assume that the attributes present in the files array are files so
		// that means that if the attribute does not have a numeric rule and
		// is not a file, we'll just consider it a string by elimination.
		elseif (array_key_exists($attribute, $this->files))
		{
			$type = 'file';
		}
		else
		{
			$type = 'string';
		}

		// Finally we can format the validation key and return the message from
		// the translation service. The validation rule will be suffixed by
		// the type of attribute that it applies for: number, file, etc.
		$key = "validation.{$lowerRule}.{$type}";

		return $this->translator->trans($key);
	}

	/**
	 * Replace all error message place-holders with actual values.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function doReplacements($message, $attribute, $rule, $parameters)
	{
		$message = str_replace(':attribute', $this->getAttribute($attribute), $message);

		if (method_exists($this, $replacer = "replace{$rule}"))
		{
			$message = $this->$replacer($message, $attribute, $rule, $parameters);
		}

		return $message;
	}

	/**
	 * Replace all place-holders for the between rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceBetween($message, $attribute, $rule, $parameters)
	{
		return str_replace(array(':min', ':max'), $parameters, $message);
	}

	/**
	 * Replace all place-holders for the size rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceSize($message, $attribute, $rule, $parameters)
	{
		return str_replace(':size', $parameters[0], $message);
	}

	/**
	 * Replace all place-holders for the min rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceMin($message, $attribute, $rule, $parameters)
	{
		return str_replace(':min', $parameters[0], $message);
	}

	/**
	 * Replace all place-holders for the max rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceMax($message, $attribute, $rule, $parameters)
	{
		return str_replace(':max', $parameters[0], $message);
	}

	/**
	 * Replace all place-holders for the in rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceIn($message, $attribute, $rule, $parameters)
	{
		return str_replace(':values', implode(', ', $parameters), $message);
	}

	/**
	 * Replace all place-holders for the not_in rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceNotIn($message, $attribute, $rule, $parameters)
	{
		return str_replace(':values', implode(', ', $parameters), $message);
	}

	/**
	 * Replace all place-holders for the not_in rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceMimes($message, $attribute, $rule, $parameters)
	{
		return str_replace(':values', implode(', ', $parameters), $message);
	}

	/**
	 * Replace all place-holders for the same rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceSame($message, $attribute, $rule, $parameters)
	{
		return str_replace(':other', $parameters[0], $message);
	}

	/**
	 * Replace all place-holders for the different rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceDifferent($message, $attribute, $rule, $parameters)
	{
		return str_replace(':other', $parameters[0], $message);
	}

	/**
	 * Replace all place-holders for the before rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceBefore($message, $attribute, $rule, $parameters)
	{
		return str_replace(':date', $parameters[0], $message);
	}

	/**
	 * Replace all place-holders for the after rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceAfter($message, $attribute, $rule, $parameters)
	{
		return str_replace(':date', $parameters[0], $message);
	}

	/**
	 * Determine if the given attribute has a rule in the given set.
	 *
	 * @param  string  $attribute
	 * @param  array   $rules
	 * @return bool
	 */
	protected function hasRule($attribute, $rules)
	{
		// To determine if the attribute has a rule in the ruleset, we will spin
		// through each of the rules assigned to the attribute and parse them
		// all, then check to see if the parsed rule exists in the array.
		foreach ($this->rules[$attribute] as $rule)
		{
			list($rule, $parameters) = $this->parseRule($rule);

			if (in_array($rule, $rules)) return true;
		}

		return false;
	}

	/**
	 * Extract the rule name and parameters from a rule.
	 *
	 * @param  string  $rule
	 * @return array
	 */
	protected function parseRule($rule)
	{
		$parameters = array();

		// The format for specifying validation rules and parameters follows an
		// easy {rule}:{parameters} formatting convention. For instance the
		// rule "Max:3" states that the value may only be three letters.
		if (($colon = strpos($rule, ':')) !== false)
		{
			$parameters = str_getcsv(substr($rule, $colon + 1));
		}

		$rule = is_numeric($colon) ? substr($rule, 0, $colon) : $rule;

		return array($rule, $parameters);
	}

	/**
	 * Get the data under validation.
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Get the validation rules.
	 *
	 * @return array
	 */
	public function getRules()
	{
		return $this->rules;
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