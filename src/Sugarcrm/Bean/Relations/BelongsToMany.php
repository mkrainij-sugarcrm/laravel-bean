<?php namespace Sugarcrm\Bean\Relations;

use Sugarcrm\Bean\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;

class BelongsToMany extends EloquentBelongsToMany
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
    public function get($columns = array('*'))
    {
        return $this->query->setupParent($this->getParent()->getModule(), $this->getForeignKey())->get($columns);
    }


    /**
     * Attach a model to the parent.
     *
     * @param  mixed $id
     * @param  array $attributes
     * @param  bool $touch - not implemented
     *
     * @return void
     */
    public function attach($id, array $attributes = array(), $touch = true)
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        $related = $this->query->getQuery()->getConnection()->relate($this->getParent()->getModule(), $this->getParent()->getKey(),
            $this->getTable(), $id, $attributes);

        // we're getting both beans back, but I'm not sure if need to do this
        if(array_key_exists('record',$related)) {
            $this->getParent()->fill($related['record']);
        }

        if(array_key_exists('related_record',$related)){
            $this->getRelated()->fill($related['related_record']);
        }
    }
}