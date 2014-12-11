<?php namespace Sugarcrm\Bean\Query;


class Builder extends \Illuminate\Database\Query\Builder {



    /**
     * Compile the where array.
     *
     * @return array
     */
    protected function compileWheres()
    {
        // The wheres to compile.
        $wheres = $this->wheres ?: array();

        // We will add all compiled wheres to this array.
        $compiled = array();

        return $compiled;
    }
} 