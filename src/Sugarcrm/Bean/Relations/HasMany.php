<?php namespace Sugarcrm\Bean\Relations;

use Sugarcrm\Bean\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;

class HasMany extends EloquentHasMany
{

    /**
     *
     * We have to overwrite get() since it will be passed directly to  a query builder but we need to setup parent beforehand
     * Probably not ideal, but this is all I can think of right now
     *
     * @param array $columns
     *
     * @return mixed
     *
     */
    public function get(array $columns = [])
    {
        return $this->query->setupParent($this->getParent()->getModule(), $this->getForeignKey())->get($columns);
    }

}
