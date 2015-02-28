<?php namespace Sugarcrm\Bean\Eloquent;

use Sugarcrm\Bean\Query\Builder as QueryBuilder;

class Builder extends \Illuminate\Database\Eloquent\Builder
{
    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }
}