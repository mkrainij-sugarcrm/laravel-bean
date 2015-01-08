<?php namespace Sugarcrm\Bean\Query;

use Sugarcrm\Bean\Connection;

class Builder extends \Illuminate\Database\Query\Builder
{

    /**
     * The maximum number of records to return.
     *
     * @var int
     */
    public $limit = 1000; // had limit to prevent performance issues

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = array(
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        'like',
        'starts',
        'ends'
//        'not like',
//        'between',
//        'ilike',
//        '&',
//        '|',
//        '^',
//        '<<',
//        '>>',
//        'exists',
//        'type',
//        'mod',
//        'where',
//        'all',
//        'size',
//        'regex',
//        'elemmatch'
    );

    /**
     * Operator conversion.
     *
     * @var array
     */
    protected $conversion = [
        '='      => '$equals',
        'like'   => '$contains',
        'starts' => '$starts',
        'ends'   => '$ends',
        '!='     => '$not_equals',
        '<>'     => '$ne',
        '<'      => '$lt',
        '<='     => '$lte',
        '>'      => '$gt',
        '>='     => '$gte',
    ];

    /**
     * Create a new query builder instance.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute the query as a fresh "select" statement.
     *
     * @param  array $columns
     *
     * @return array|static[]
     */
    public function getFresh($columns = array('*'))
    {
        $start = microtime(true);

        // If no columns have been specified for the select statement, we will set them
        // here to either the passed columns, or the standard default of retrieving
        // all of the columns on the table using the "wildcard" column character.
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        // Drop all columns if * is present, SugarCRM does not work this way.
        if (in_array('*', $this->columns)) {
            $this->columns = array();
        }

        $wheres = $this->compileWheres();

        // sugar wants it wrapped
        if (!empty($wheres)) {
            $wheres = [$wheres];
        }

        // Compile wheres
        $params = [
            'filter'   => $wheres,
            'max_num'  => $this->limit,
            'offset'   => $this->offset,
            'fields'   => '', // by default we're getting all of them, but we're going to double check bellow
            'order_by' => $this->orders,
            'deleted'  => false,
        ];

        // set fields to select from SugarCRM. Keep in mind that data date_modified will be returned all the time
        if (!empty($this->columns)) {
            $params['fields'] = implode(',', $this->columns);
        }

        //
        $result = $this->connection->filter($this->from . '/filter', $params);

        if (is_null($result) || !array_key_exists('records', $result)) {
            return []; /// going to send empty array for now
        }

        //@TODO technically we can implement quering till we get results up to the limit, but it seems like bad idea right now

        // Log query
        $this->connection->logQuery(
            $this->from . '.find(' . json_encode($wheres) . ', ' . json_encode($columns) . ')',
            array(), $this->connection->getElapsedTime($start));

        // Return results as an array with numeric keys
        return $result['records'];

    }


    /**
     * Compile the where array.
     *
     * @return array
     */
    protected function compileWheres()
    {
        // The wheres to compile.
        $wheres = $this->wheres ?: array();

        echo '<pre>';
        print_r($wheres);
        echo '</pre>';


        // We will add all compiled wheres to this array.
        $compiled = array();

        foreach ($wheres as $i => &$where) {
            // Make sure the operator is in lowercase.
            if (isset($where['operator'])) {
                $where['operator'] = strtolower($where['operator']);
            }

            // Convert DateTime values to MongoDate.
            if (isset($where['value']) && $where['value'] instanceof DateTime) {
                $where['value'] = $where['value']->format('c');
            }

            // The next item in a "chain" of wheres devices the boolean of the
            // first item. So if we see that there are multiple wheres, we will
            // use the operator of the next where.
            if ($i == 0 and count($wheres) > 1 and $where['boolean'] == 'and') {
                $where['boolean'] = $wheres[$i + 1]['boolean'];
            }

            // We use different methods to compile different wheres.
            $method = "compileWhere{$where['type']}";
            $result = $this->{$method}($where);

            // Wrap the where with an $or operator.
            if ($where['boolean'] == 'or') {
                $result = array('$or' => array($result));
            }

            // If there are multiple wheres, we will wrap it with $and. This is needed
            // to make nested wheres work.
            else {
                if (count($wheres) > 1) {
                    $result = array('$and' => array($result));
                }
            }

            // Merge the compiled where with the others.
            $compiled = array_merge_recursive($compiled, $result);
        }

        return $compiled;
    }

    /**
     * @param $where
     *
     * @return array
     */
    protected function compileWhereBasic($where)
    {
        extract($where);
        if (!isset($operator) or $operator == '=') {
            $query = array($column => $value);
        } else {
            if (array_key_exists($operator, $this->conversion)) {
                $query = array($column => array($this->conversion[$operator] => $value));
            } else {
                $query = array($column => array('$' . $operator => $value));
            }
        }

        return $query;
    }

    public function whereStartsWith($column, $values, $boolean = 'and')
    {
        $type = 'starts';

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        return $this;
    }

    public function whereEndsWith($column, $values, $boolean = 'and')
    {
        $type = 'ends';

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        return $this;
    }

    protected function compileWhereStarts($where)
    {
        extract($where);

        return array($column => array('$starts' => $values));
    }

    protected function compileWhereEndsWith($where)
    {
        extract($where);

        return array($column => array('$ends' => $values));
    }

    protected function compileWhereIn($where)
    {
        extract($where);

        return array($column => array('$in' => $values));
    }

    protected function compileWhereNotIn($where)
    {
        extract($where);

        return array($column => array('$not_in' => $values));
    }

    protected function compileWhereNull($where)
    {
        extract($where);

        return array($column => array('$is_null' => ''));

    }

    protected function compileWhereNotNull($where)
    {

        extract($where);

        return array($column => array('$not_null' => ''));

    }

    /**
     * Get a new instance of the query builder.
     *
     * @return Builder
     */
    public function newQuery()
    {
        return new Builder($this->connection);
    }

} 