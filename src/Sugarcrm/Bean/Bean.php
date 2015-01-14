<?php namespace Sugarcrm\Bean;

use Sugarcrm\Bean\Query\Builder as QueryBuilder,
    Sugarcrm\Bean\Eloquent\Builder,
    Sugarcrm\Bean\Relations\HasMany,
    Sugarcrm\Bean\Relations\BelongsToMany;


abstract class Bean extends \Illuminate\Database\Eloquent\Model
{

    protected $guarded = [];

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
     * @return \Sugarcrm\Bean\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        return new QueryBuilder($conn);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Sugarcrm\Bean\Query\Builder $query
     *
     * @return \Sugarcrm\Bean\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Update the model in the database.
     *
     * @param  array $attributes
     *
     * @return bool|int
     */
    public function update(array $attributes = array())
    {
        if (!$this->exists) {
            return $this->newQuery()->update($attributes);
        }

        return $this->fill($attributes)->save();
    }


    /**
     * Save the model to the database.
     *
     * @param  array $options
     *
     * @return bool
     */
    public function save(array $options = array())
    {
        $query = $this->newQueryWithoutScopes();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This gives an opportunities to
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists) {
            $saved = $this->updateBean($query);
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->insertBean($query);
        }

        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    protected function updateBean(Builder $query, array $options = [])
    {

    }


    protected function insertBean(Builder $query, array $options = [])
    {

        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        $attributes = $this->attributes;

        $insert = $query->insert($attributes);

        if (!$insert) {
            return false;
        }

        // fill in data from API
        $this->fill($insert);

        // set flag
        $this->exists = true;

        $this->fireModelEvent('created', false);

        return true;
    }


    /**
     * Fire the given event for the model.
     *
     * @param  string $event
     * @param  bool $halt
     *
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        if (!isset(static::$dispatcher)) {
            return true;
        }

        // We will append the names of the class to the event to distinguish it from
        // other model events that are fired, allowing us to listen on each model
        // event set individually instead of catching event for all the models.
        $event = "bean.{$event}: " . get_class($this);

        $method = $halt ? 'until' : 'fire';

        return static::$dispatcher->$method($event, $this);
    }


    /**
     * Get the table qualified key name.
     *
     * @return string
     */
    public function getQualifiedKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * Get the module associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (isset($this->module)) return $this->module;

        return ucwords(str_replace('\\', '', snake_case(str_plural(rtrim(class_basename($this),'Bean')))));
    }

    /**
     * Alias for getTable()
     *
     * @return string
     */
    public function getModule()
    {
        return $this->getTable();
    }

    /**
     * Set the module associated with the model.
     *
     * @param  string  $table
     * @return void
     */
    public function setTable($table)
    {
        $this->module = $table;
    }

    /**
     * Alias for setTable($table)
     *
     * @param $module
     */
    public function setModule($module)
    {
        $this->setTable($module);
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related;

        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    /**
     * Define a many-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $link
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToMany($related, $link = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation))
        {
            $relation = $this->getBelongsToManyCaller();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related;

        $otherKey = $otherKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($link))
        {
            $link = $this->getModule();
        }

        // Now we're ready to create a new query builder for the related model and
        // the relationship instances for the relation. The relations will set
        // appropriate query constraint and entirely manages the hydrations.
        $query = $instance->newQuery();

        return new BelongsToMany($query, $this, $link, $foreignKey, $otherKey, $relation);
    }

}