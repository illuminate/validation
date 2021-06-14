<?php

namespace Illuminate\Validation\Rules;

class RequiredIf
{
    /**
     * The condition that validates the attribute.
     *
     * @var callable|bool
     */
    public $condition;

    /**
     * Create a new required validation rule based on a condition.
     *
     * @param  callable|bool  $condition
     * @return void
     */
    public function __construct($condition)
    {
        if (! is_string($condition) && (is_bool($condition) || is_callable($condition))) {
            $this->condition = $condition;
        } else {
            throw new \InvalidArgumentException("Condition type must be 'callable' or 'bool'.");
        }
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        if (is_callable($this->condition)) {
            return call_user_func($this->condition) ? 'required' : '';
        }

        return $this->condition ? 'required' : '';
    }
}
