<?php namespace Sugarcrm\Bean;

use Closure;
use Sugarcrm\Bean\Api\v10;

class Connection extends \Illuminate\Database\Connection
{
    /**
     * The SugarCRM connection handler.
     *
     * @var resource
     */
    protected $connection;

    /**
     * Create a new api connection instance.
     *
     * @param  array   $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // You can pass options directly to the Curl Client constructor
        $options = array_get($config, 'options', array());

        // Create the connection
        $this->connection = $this->createConnection($config, $options);
    }

    public function createConnection(array $config, array $options)
    {
        $connection = new v10($config, $options);

        return $connection->connect($config);
    }

    /**
     * Begin a fluent query against a database collection.
     *
     * @param  string  $collection
     * @return QueryBuilder
     */
    public function collection($collection)
    {
        $query = new QueryBuilder($this);

        return $query->from($collection);
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int    $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function insert($query, $bindings = array())
    {
        return $this->connection->insert($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function update($query, $bindings = array())
    {
        return $this->connection->update($query, $bindings);
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param  string $table
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table)
    {
        $query = new Query\Builder($this);

        return $query->from($table);
    }

    /**
     * Get a new raw query expression.
     *
     * @param  mixed $value
     *
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value)
    {
        // TODO: Implement raw() method.
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return mixed
     */
    public function selectOne($query, $bindings = array())
    {
        // TODO: Implement selectOne() method.
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return array
     */
    public function select($query, $bindings = array())
    {
        // TODO: Implement select() method.
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return int
     */
    public function delete($query, $bindings = array())
    {
        // TODO: Implement delete() method.
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return bool
     */
    public function statement($query, $bindings = array())
    {
        // TODO: Implement statement() method.
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return int
     */
    public function affectingStatement($query, $bindings = array())
    {
        // TODO: Implement affectingStatement() method.
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string $query
     *
     * @return bool
     */
    public function unprepared($query)
    {
        // TODO: Implement unprepared() method.
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array $bindings
     *
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        // TODO: Implement prepareBindings() method.
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure $callback
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function transaction(Closure $callback)
    {
        // TODO: Implement transaction() method.
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction()
    {
        // TODO: Implement beginTransaction() method.
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit()
    {
        // TODO: Implement commit() method.
    }

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack()
    {
        // TODO: Implement rollBack() method.
    }

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel()
    {
        // TODO: Implement transactionLevel() method.
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param  \Closure $callback
     *
     * @return array
     */
    public function pretend(Closure $callback)
    {
        // TODO: Implement pretend() method.
    }


    /**
     * Dynamically pass methods to the connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $parameters);
        }

        return call_user_func_array(array($this->connection, $method), $parameters);
    }
}