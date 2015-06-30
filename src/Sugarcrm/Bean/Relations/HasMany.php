<?php namespace Sugarcrm\Bean\Relations;

use Illuminate\Database\Eloquent\Collection;
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
        $results = $this->query->getQuery()->getConnection()->related(
            $this->getParent()->getTable(),
            $this->getParent()->id,
            $this->getForeignKey(),
            ['fields' => $columns]
        );

        if(!array_key_exists('records',$results)){

            $this->initRelation([$this->getParent()],$this->getForeignKey());

            return $this->getParent()->getRelation($this->getForeignKey());
        }

        foreach ($results['records'] as $i => $result) {
            $results['records'][$i] = $this->related->newInstance($result);
        }

        $this->getParent()->setRelation($this->getForeignKey(), $this->related->newCollection($results['records']));

        return $this->getParent()->getRelation($this->getForeignKey());
    }
}
