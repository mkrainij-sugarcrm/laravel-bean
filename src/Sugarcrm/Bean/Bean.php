<?php namespace Sugarcrm\Bean;


/**
 * This is abstract class and you have to create a model to work with SugarCRM module
 *
 * Basic use: $bugs = Sugar\Bug::find('50d10655-68cd-5cf4-d098-4e83c5d2f71c');
 *
 * Class Bean
 * @package Sugar
 *
 */
abstract class Bean extends \Eloquent implements \Sugarcrm\Bean\Interfaces\BeanInterface
{
    /**
     * The module name (plural) from SugarCRM.
     *
     * @var string
     */
    public $module;

    /**
     * The module metadata from SugarCRM.
     *
     * @var array
     */
    public $metadata;

    /**
     * The humanly readable data from API.
     *
     * @var array
     */
    public $mapped;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Allow write whatever
     *
     * @var bool
     */
    public static $unguarded = true;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $metadataStore;

    /**
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cacheDriver;

    /**
     *
     * default format for the date from API
     *
     * @var string
     */
    protected $dateFormat = \DateTime::ISO8601;

    public function __construct(array $attributes = array())
    {
        // default v4 Connect
        $this->connection = \App::make('SugarApi');

        // set metadata store model
        $model               = \Config::get('portal.drivers.metadata');
        $this->metadataStore = new $model();

        $model             = \Config::get('portal.drivers.cache');
        $this->cacheDriver = \App::make('cache.store');;

        // set default data
        parent::__construct($attributes);
    }

    /**
     * Get all of the models from the database.
     *
     * @param  array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function all($columns = array('*'), $limit = 1000, $offset = 0)
    {
        $instance = new static;
        // default option
        $options  = array(
            'max_num' => $limit,
            'offset'  => $offset,
        );

        // get what we need, it is faster if you know what you need
        if ($columns != array('*')) {
            $options['fields'] = implode(',', $columns);
        }
        // api call
        $res = $instance->connection->search($instance->module, $options);

        if (!empty($res)) {
            $collection = new \Illuminate\Support\Collection();
            foreach ($res['records'] as $i => $bean) {
                $bean = with(new static)->fill($bean, true);
                $collection->put($i, $bean);
            }
            return $collection;
        }
    }

    /**
     * $bean = new Bean(array('id'=>$id));
     * $bean->delete()
     *
     *
     * @return mixed
     */
    public function delete()
    {
        return $this->connection->delete($this->module, $this->id);
    }

    /**
     * static alternative to delete()
     *
     * @param $id
     *
     * @return mixed
     */
    public static function deleteBean($id)
    {
        $instance = new static;
        $instance->id = $id;
        return $instance->delete();
    }

    /**
     * Get all of the models from the database.
     *
     * @param  array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function get($columns = array('*'), $options = array(), $related = array(), $legacy = true)
    {
        $instance = new static;
        $results  = array();
        if ($legacy) {
            // get v4 API
            $instance->setConnection(\App::make('SugarSoap'));
            $instance->connection->connect();
            $res = $instance->connection->get($instance->module, $columns, $options, $related);
            if (!empty($res)) {
                $results = $res->collection;
            }
        } else {
            $options['fields'] = implode(',', $columns);
            $res               = $instance->connection->search($instance->module, $options);
            if (!empty($res['records'])) {
                $results = $res['records'];
            }
        }

        $collection = new \Illuminate\Support\Collection();
        foreach ($results as $i => $bean) {
            $bean = with(new static)->fill($bean, true);
            $collection->put($i, $bean);
        }
        return $collection;
    }

    public static function find($id, $columns = array())
    {
        $instance = new static;
        $options  = array();

        if (!empty($columns)) {
            $options['fields'] = $columns;
        }

        $bean = $instance->connection->retrieve($instance->module, $id, $options);

        return with(new static)->fill($bean, true);
    }

    /**
     *
     * Relate current record to another based on ID and Link
     *
     * use:
     * $beanObject->relate('link_name','bd380491-ff4b-830b-95a7-4b4cf5970a6b');
     *
     * @param       $link
     * @param       $id
     * @param array $fields
     *
     * @return mixed
     */
    public function relate($link, $id, $fields = array())
    {
        // relate($module, $record, $link, $related_record, $fields = array())
        $results = $this->connection->relate($this->module, $this->id, $link, $id, $fields);
        if (empty($results)) {
            \Log::critical('Bean: Failed to relate ' . $link . ' for ' . $id . ' with empty result', $fields);
            return null;
        }

        if(!isset($results['related_record'])){
            \Log::critical('Bean: Failed to relate ' . $link . ' for ' . $id, $results);
            return null;
        }

        $relatedBean              = with(new static)->fill($results['related_record'], true);
        $results['record'][$link] = $relatedBean->toArray();
        $bean                     = with(new static)->fill($results['record'], true);

        return $bean;
    }

    /**
     *
     * Unrelate current record to another based on ID and Link
     *
     * use:
     * $beanObject->unrelate('link_name','bd380491-ff4b-830b-95a7-4b4cf5970a6b');
     *
     * @param       $link
     * @param       $id
     * @param array $fields
     *
     * @return mixed
     */
    public function unrelate($link, $id)
    {
        // relate($module, $record, $link, $related_record, $fields = array())
        $results = $this->connection->unrelate($this->module, $this->id, $link, $id);
        if(empty($results))
            return null;

        $bean = with(new static)->fill($results['record'], true);

        return $bean;
    }

    /**
     *
     * Get related records
     *
     * @param       $id - id of the record to check for related records
     * @param       $link - name of the link in SI. if OOB, name is probably the related module lowercase
     * @param       $returnClass - name of the class for the related records since it is not the calling class.
     * @param array $options - standard get options
     *
     * @return mixed
     *
     */
    public static function related($id, $link, $returnClass, $options = array())
    {
        $instance = new static;

        $res = $instance->connection->related($instance->module, $id, $link, $options);
        if ($res !== false) {
            if (isset($res['error'])) {
                return null;
            } else {
                if (isset($res['records'])) {
                    $collection = new \Illuminate\Support\Collection();
                    foreach ($res['records'] as $i => $bean) {
                        $bean = with(new $returnClass)->fill($bean, true);
                        $collection->put($i, $bean);
                    }
                    return $collection;
                } else {
                    return with(new $returnClass)->fill($res, true);
                }
            }
        }
        return null;
    }

    public static function metadata()
    {
        $instance = new static;

        $key = 'metadata.' . $instance->module;
        try {
            if ($instance->cacheDriver->has($key)) {
                return $instance->cacheDriver->get($key);
            } else {
                //get the current fields from the database for this module
                $retrievedMetadata = $instance->metadataStore->findByModule($instance->module);

                //if there isn't anything in the database, go get it from SI
                if ($retrievedMetadata->isEmpty()) {
                    $retrievedMetadata = $instance->combineMetadata();
                }

                if (count($retrievedMetadata) > 0) {
                    $metadata = array();
                    foreach ($retrievedMetadata as $field) {
                        $metadata[$field->field_name] = $field->toArray();
                    }

                    //make sure to cache them for at least 24 hours
                    //$instance->cacheDriver->put($key, $metadata, \Config::get('portal.metadata_cache'));

                    return $metadata;
                }
            }
        } catch (\Exception $e) {
            error_log($instance->module . ' Cache Error: ' . $e->getMessage());
        }

        return null;

    }

    public static function lang()
    {
        $instance = new static;

        return $instance->connection->lang();
    }

    public function save(array $fields = array(), array $options = array())
    {
        // check to see what we're saving
        if (empty($fields)) {
            $fields = $this->attributes;
        }

        $query = '';
        // run to api to save stuff

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This gives an opportunities to
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if (isset($fields['id'])) {

            // send data to api
            $result = $this->connection->update($this->module, $fields['id'], $fields);
            if($result == false){
                return false;
            }

            // set data
            $this->fill($result);

            // fire even
            $this->fireModelEvent('updated', false);

            $saved = true;
        }
        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $result = $this->connection->create($this->module, json_encode($fields));

            // exits
            $this->exists = true;

            // set data
            $this->fill($result);

            // fire event
            $this->fireModelEvent('created', false);

            //
            $saved = true;
        }

        if ($saved) {
            $this->finishSave($options);
        }

        return $this;
    }

    /**
     * Hydrate the model with an array of human readable data.
     *
     * @param  array $attributes
     * @param  bool  $raw
     *
     * @return Model
     */
    public function map(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->mapped[$key] = $value;
        }

        return $this;
    }

    /**
     * Begin querying the model on a given connection.
     *
     * @param  string $connection
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function on($connection = null)
    {
        // First we will just create a fresh instance of this model, and then we can
        // set the connection on the model so that it is be used for the queries
        // we execute, as well as being set on each relationship we retrieve.
        $instance = new static;

        $instance->setConnection($connection);

        return $instance;
    }

    public function combineMetadata()
    {
        $metadata = array();

        //get the stuff from SI for all modules (no way to get just one module of stuff)
        $sifields = $this->connection->metadata();

        $silabels = $this->lang();

        // add function generated data to the list
        $functionGeneratedLists = $this->metadataStore->findByModule('System');
        if (!is_null($functionGeneratedLists)) {
            foreach ($functionGeneratedLists as $fgl) {
                $silabels['app_list_strings'][$fgl->field_name] = $fgl->options_list;
            }
        }

        //get all of the fields and options and labels
        $options_names = array();
        $label_names   = array();

        foreach ($sifields['modules'][$this->module]['fields'] as $fieldname => $field_info) {
            $fieldname = $field_info['name'];

            if ($field_info['type'] == 'enum' && isset($field_info['options'])) {
                $options_names[$fieldname] = $field_info['options'];
            } elseif ($field_info['type'] == 'enum' && isset($field_info['function']) && !empty($field_info['function'])) {
                $options_names[$fieldname] = $field_info['function'];
            }
            $label_names[$fieldname] = (is_array($field_info) && array_key_exists('vname', $field_info)) ? $field_info['vname'] : $fieldname;
            $metadata[$fieldname]    = $field_info;
        }

        foreach ($options_names as $fieldname => $listname) {
            //special for options that are releases
            if (array_key_exists($listname, $silabels['app_list_strings'])) {
                $metadata[$fieldname]['options_list'] = $silabels['app_list_strings'][$listname];
            } else {
                $metadata[$fieldname]['options_list'] = array();
            }
        }
        foreach ($label_names as $fieldname => $labelname) {

            $label_text = (array_key_exists($labelname, $silabels['mod_strings'][$this->module])) ? trim(
                $silabels['mod_strings'][$this->module][$labelname]
            ) : $fieldname;

            if ($label_text[strlen($label_text) - 1] == ':') {
                $label_text = substr($label_text, 0, -1);
            }
            $metadata[$fieldname]['vname'] = $label_text;
        }

        //get the current fields from the database for this module
        $dbfields = $this->metadataStore->findByModule($this->module);

        //update any of the ones currently in the db
        foreach ($dbfields as $field) {
            //if the field is in the current metadata, update it
            //else, delete the field
            if (isset($metadata[$field->field_name])) {
                $field->display_name = $metadata[$field->field_name]['vname'];
                $field->field_type   = $metadata[$field->field_name]['type'];
                if (isset($metadata[$field->field_name]['options_list'])) {
                    $field->options_list = $metadata[$field->field_name]['options_list'];
                }
                // touch and save
                $field->touch();
                unset($metadata[$field->field_name]);
            } else {
                $field->delete();
            }
        }

        //if there are any metadata left, add the new fields
        if (!empty($metadata)) {
            foreach ($metadata as $fieldName => $fieldInfo) {
                $newValues = array(
                    'parent_type'  => $this->module,
                    'field_name'   => $fieldName,
                    'display_name' => $fieldInfo['vname'],
                    'field_type'   => $fieldInfo['type']
                );
                if (isset($fieldInfo['options_list'])) {
                    $newValues['options_list'] = json_encode($fieldInfo['options_list']);
                }
                $newField = $this->metadataStore->newInstance($newValues);
                $newField->save();
            }
        }

        //return the current fields from the database for this module
        return $this->metadataStore->findByModule($this->module);
    }


    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    |
    | Eloquent provides a convenient way to transform your model attributes when getting or setting them.
    | Simply define a getFooAttribute method on your model to declare an accessor.
    | Keep in mind that the methods should follow camel-casing, even though your database columns are snake-case:
    | like getFirstNameAttribute
    |
    */

    /**
     * Format helper for all dates from API. v10 api send data in users time zone and in ISO format, old API sends DB date in UTC format.
     *
     * @param $value
     *
     * @return \Carbon\Carbon
     */
    public function getUTCDate($value){
        if (empty($value)) {
            return \Carbon\Carbon::now()->setTimezone('UTC');
        }

        $format = $this->dateFormat;
        if (stripos($value, 'T') === false) {
            $format = 'Y-m-d H:i:s';
        }
        return \Carbon\Carbon::createFromFormat($format, $value)->setTimezone('UTC');

    }

    /**
     * @param $value
     *
     * @return \Carbon\Carbon
     */
    public function getDateModifiedAttribute($value)
    {
        return $this->getUTCDate($value);
    }

    /**
     * @param $value
     *
     * @return \Carbon\Carbon
     */
    public function getDateEnteredAttribute($value)
    {
        return $this->getUTCDate($value);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        // The string sometimes comes in double-encoded already for some reason
        $value = htmlDecode($value);
        $value = htmlDecode($value);

        // Remove br tags which are already in there
        $value = preg_replace('#<br\s*/>\n#U', "\n", $value);

        // Re-encode
        $value = htmlEncode($value);

        // Add back the br tags we removed
        $value = nl2br($value);

        return $value;
    }

    /**
     * @param $value
     */
    public function setDescriptionAttribute($value)
    {
        // strip MS word BS
        $this->attributes['description'] = htmlspecialchars($value, ENT_NOQUOTES, "UTF-8");
    }

    /**
     * Passing email will add new email with opt_out = false to the record.
     * add force to overwrite array. not sure if API will take it
     *
     * @param $value - email|array
     */
    public function setEmailAttribute($value)
    {
        if (empty($value)) return false;

        if (!is_array($value)) {
            $value = array(
                'email_address' => $value,
                'opt_out'       => false
            );

            // force array overwrite or set new array if we have no value
            if (array_key_exists('force', $value) || !array_key_exists('email',$this->attributes) || !is_array($this->attributes)) {
                $this->attributes['email'] = array($value);
            } else {
                $this->attributes['email'][] = $value;
            }
        } else{
            $this->attributes['email'] = $value;
        }
    }

    /**
     * @param $value
     */
    public function setNameAttribute($value)
    {
        // Prevent double-encoding in case it's already encoded
        $value = htmlDecode($value);
        // strip MS word BS
        $this->attributes['name'] = htmlspecialchars($value, ENT_NOQUOTES, "UTF-8");
    }

    public function getValueLabel($filed)
    {
        $m = $this->metadata();

        $opt_list = $this->getOptionsListAttribute($m[$filed]['options_list']);

        if (!array_key_exists($filed, $m)) {
            return $filed;
        }

        if (!array_key_exists('options_list', $m[$filed]) || empty($opt_list)) {
            return $filed;
        }

        if (!array_key_exists($this->attributes[$filed], $opt_list) && !empty($opt_list)) {
            return 'N/A';
        }

        return $opt_list[$this->attributes[$filed]];
    }

    public static function getLabelFromMetadata($fieldName, $value, $metadata)
    {
        if (!array_key_exists($fieldName, $metadata)) {
            return '';
        }
        $list = $metadata[$fieldName]['options_list'];
        if(!is_array($metadata[$fieldName]['options_list'])){
            $list = json_decode($metadata[$fieldName]['options_list'],true);
        }
        return $list[$value];
    }

    /*************************
     *
     * Not sure about functions bellow
     *
     *
     **************************/

    public static function makeDisplayDate($val)
    {
        $format = 'm/d/Y H:i';
        return date($format, strtotime($val));
    }

    public static function makeDisplayText($val)
    {
        return str_replace("\n", "<br>", $val);
    }

    public static function makeDisplayBoolean($val)
    {
        return ($val == 1) ? "Yes" : "No";
    }

    public static function getSelectText($options, $val)
    {
        //return the option with the passed in val
        if (isset($options[$val])) {
            return $options[$val];
        }
        return $val;
    }

    public static function getOptionsListAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        } else {
            return json_decode($value, true);
        }
    }


}
