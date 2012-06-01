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
	 * The message bag instance.
	 *
	 * @var Illuminate\Validation\MessageBag
	 */
	protected $messages;

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
	protected $files = array();

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
		$this->translator = $translator;
		$this->rules = $this->explodeRules($rules);
	}

	/**
	 * Explode the rules into an array of rules.
	 *
	 * @param  string|array  $rules
	 * @return array
	 */
	protected function explodeRules($rules)
	{
		foreach ($rules as $key => &$rule)
		{
			$rule = (is_string($rule)) ? explode('|', $rule) : $rule;
		}

		return $rules;		
	}

	/**
	 * Determine if the data passes the validation rules.
	 *
	 * @return bool
	 */
	public function passes()
	{
		$this->errors = new MessageBag;

		// We'll spin through each rule, validating the attributes attached to
		// that rule. Any error messages will be added to the container with
		// all of the other error messages, and return true if we get them.
		foreach ($this->rules as $attribute => $rules)
		{
			foreach ($rules as $rule)
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
		$value = $this->getValue($attribute);

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
	 * Validate that an attribute is different from another attribute.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateDifferent($attribute, $value, $parameters)
	{
		$other = $parameters[0];

		return isset($this->attributes[$other]) and $value != $this->attributes[$other];
	}

	/**
	 * Validate that an attribute was "accepted".
	 *
	 * This validation rule implies the attribute is "required".
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateAccepted($attribute, $value)
	{
		return $this->validateRequired($attribute, $value) and ($value == 'yes' or $value == '1');
	}

	/**
	 * Validate that an attribute is numeric.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateNumeric($attribute, $value)
	{
		return is_numeric($value);
	}

	/**
	 * Validate that an attribute is an integer.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateInteger($attribute, $value)
	{
		return filter_var($value, FILTER_VALIDATE_INT) !== false;
	}

	/**
	 * Validate the size of an attribute.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateSize($attribute, $value, $parameters)
	{
		return $this->getSize($attribute, $value) == $parameters[0];
	}

	/**
	 * Validate the size of an attribute is between a set of values.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateBetween($attribute, $value, $parameters)
	{
		$size = $this->getSize($attribute, $value);

		return $size >= $parameters[0] and $size <= $parameters[1];
	}

	/**
	 * Validate the size of an attribute is greater than a minimum value.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateMin($attribute, $value, $parameters)
	{
		return $this->getSize($attribute, $value) >= $parameters[0];
	}

	/**
	 * Validate the size of an attribute is less than a maximum value.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateMax($attribute, $value, $parameters)
	{
		return $this->getSize($attribute, $value) <= $parameters[0];
	}

	/*
	 * Get the size of an attribute.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return mixed
	 */
	protected function getSize($attribute, $value)
	{
	 	// This method will determine if the attribute is a number, string, or file and
	 	// return the proper size accordingly. If it is a number, then number itself
	 	// is the size. If it is a file, we take kilobytes, and for a string the
	 	// entire length of the string will be considered the attribute size.
		if (is_numeric($value) and $this->hasRule($attribute, $this->numericRules))
		{
			return $this->attributes[$attribute];
		}
		elseif (array_key_exists($attribute, $this->files))
		{
			return $value['size'] / 1024;
		}
		else
		{
			return $this->getStringSize($value);
		}
	}

	/**
	 * Get the size of a string.
	 *
	 * @param  string  $value
	 * @return int
	 */
	protected function getStringSize($value)
	{
		if (function_exists('mb_strlen')) return mb_strlen($value);

		return strlen($value);
	}

	/**
	 * Validate an attribute is contained within a list of values.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateIn($attribute, $value, $parameters)
	{
		return in_array($value, $parameters);
	}

	/**
	 * Validate an attribute is not contained within a list of values.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateNotIn($attribute, $value, $parameters)
	{
		return ! in_array($value, $parameters);
	}

	/**
	 * Validate the uniqueness of an attribute value on a given database table.
	 *
	 * If a database column is not specified, the attribute will be used.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateUnique($attribute, $value, $parameters)
	{
		$table = $parameters[0];

		// The second parameter position holds the name of the column that should
		// be verified as unique. If this parameter is not specified we will
		// assume that the column to be verified has the attribute name.
		if (isset($parameters[1]))
		{
			$column = $parameters[1];
		}
		else
		{
			$column = $attribute;
		}

		// The third parameter spot holds the ID value that will be excluded from
		// the query when checking for uniqueness. This is useful for ignoring
		// the current value of e-mail address fields when updating a user.
		if (isset($parameters[2]))
		{
			$idColumn = isset($parameters[3]) ? $parameters[3] : 'id';

			$excludeId = $parameters[2];
		}

		// Finally we get an instance of the presence verifier implementation and
		// verify that the value is in fact unique for the given column on the
		// data store being checked by the presence verifier implementation.
		$verifier = $this->getPresenceVerifier();

		return $verifier->verifyUnique($table, $column, $excludeId, $idColumn);
	}

	/**
	 * Validate the existence of an attribute value in a database table.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateExists($attribute, $value, $parameters)
	{
		$table = $parameters[0];

		// The second parameter position holds the name of the column that should
		// be verified as existing. If this parameter is not specified we'll
		// assume that the column to be verified has the attribute name.
		if (isset($parameters[1]))
		{
			$column = $parameters[1];
		}
		else
		{
			$column = $attribute;
		}

		$expectedCount = (is_array($value)) ? count($value) : 1;

		// If the given value is actually an array, we will tell the verifier we
		// need to count the existence of multiple objects so it can utilize
		// a "where in" type of statement when querying the data source.
		$verifier = $this->getPresenceVerifier();

		if (is_array($value))
		{
			$actualCount = $verifier->getMultiCount($table, $column, $value);
		}
		else
		{
			$actualCount = $verifier->getCount($table, $column, $value);
		}

		// Finally, if the actual count of objects matching the given values in
		// our parameters matches the number of parameter values we can know
		// that all of the values given actually exist in the data store.
		return $actualCount >= $expectedCount;
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

		$customKey = "validation.{$attribute}.{$lowerRule}";

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
	 * Get the displayable name of the attribute.
	 *
	 * @param  string  $attribute
	 * @return string
	 */
	protected function getAttribute($attribute)
	{
		// We allow for the developer to specify language lines for each of the
		// attributes allowing for more displayable counterparts of each of
		// the attributes. This provides the ability for simple formats.
		$key = "validation.attributes.{$attribute}";

		if (($line = $this->translator->trans($key)) !== $key)
		{
			return $line;
		}

		// If no language line has been specified for the attribute all of the
		// underscores are removed from the attribute name and that will be
		// used as default versions of the attribute's displayable name.
		else
		{
			return str_replace('_', ' ', $attribute);
		}
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
		if ( ! isset($this->presenceVerifier))
		{
			throw new \RuntimeException("Presence verifier has not been set.");
		}

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