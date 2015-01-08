<?php namespace Sugarcrm\Bean;

use Sugarcrm\Bean\Query\Builder as QueryBuilder;

abstract class Bean extends \Illuminate\Database\Eloquent\Model
{

    /**********************************************************************
     *  SoftDelete:
     **********************************************************************
     *
     * Due to the nature of SugarCRM all deletes are soft deletes.
     * We may implement withTrashed at some point to may it more explicit, but for now please use where('deleted','=',true)
     *
     */


    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'sugarcrm';

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        return new QueryBuilder($conn);
    }
}