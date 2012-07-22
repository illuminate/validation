<?php namespace Illuminate\Validation;

use Illuminate\Database\Connection;

class DatabasePresenceVerifier implements PresenceVerifierInterface {

	/**
	 * The database connection instance.
	 *
	 * @var  Illuminate\Database\Connection
	 */
	protected $db;

	/**
	 * Create a new database presence verifier.
	 *
	 * @param  Illuminate\Database\Connection  $db
	 * @return void
	 */
	public function __construct(Connection $db)
	{
		$this->db = $db;
	}

	/**
	 * Count the number of objects in a collection having the given value.
	 *
	 * @param  string  $collection
	 * @param  string  $column
	 * @param  string  $value
	 * @param  int     $excludeId
	 * @param  string  $idColumn
	 * @return int
	 */
	public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null)
	{
		$query = $this->db->table($collection)->where($column, '=', $value);

		if ( ! is_null($excludeId))
		{
			$query->where($idColumn ?: 'id', '<>', $excludeId);
		}

		return $query->count();
	}

	/**
	 * Count the number of objects in a collection with the given values.
	 *
	 * @param  string  $collection
	 * @param  string  $column
	 * @param  array   $values
	 * @return int
	 */
	public function getMultiCount($collection, $column, array $values)
	{
		$query = $this->db->table($collection)->whereIn($column, $values)->count();
	}

}