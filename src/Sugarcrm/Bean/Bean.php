<?php namespace Sugarcrm\Bean;

use Sugarcrm\Bean\Query\Builder as QueryBuilder;

abstract class Bean extends \Illuminate\Database\Eloquent\Model
{

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor());
    }


}