<?php namespace Sugarcrm\Bean;

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