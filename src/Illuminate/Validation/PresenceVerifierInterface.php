<?php namespace Illuminate\Validation;

interface PresenceVerifierInterface {

	/**
	 * Verify that a given value is unique on a data collection.
	 *
	 * @param  string  $collection
	 * @param  string  $column
	 * @param  int     $excludeId
	 * @param  string  $idColumn
	 * @return bool
	 */
	public function verifyUnique($collection, $column, $excludeId, $idColumn);

	/**
	 * Count the number of objects in a collection having the given value.
	 *
	 * @param  string  $collection
	 * @param  string  $column
	 * @param  string  $value
	 * @return int
	 */
	public function getCount($collection, $column, $value);

	/**
	 * Count the number of objects in a collection with the given values.
	 *
	 * @param  string  $collection
	 * @param  string  $column
	 * @param  array   $values
	 * @return int
	 */
	public function getMultiCount($collection, $column, array $values);

}