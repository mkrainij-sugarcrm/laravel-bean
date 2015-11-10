<?php namespace Sugarcrm\Bean\Api;

use Sugarcrm\Bean\Cache\FieldMetadata;
use Faker\Factory;

class v10
{
    /**
     * Variable: $config
     * Description:  A SugarCRM User.
     */
    private $config;

    /**
     * Variable: $token
     * Description:  OAuth 2.0 token
     */
    private $token;

    private $faker;

    /**
     * Function: __construct()
     * Parameters:   none
     * Description:  Construct Class
     * Returns:  VOID
     */
    function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * Function: __destruct()
     * Parameters:   none
     * Description:  OAuth2 Logout
     * Returns:  TRUE on success, otherwise FALSE
     */
    function __destruct()
    {
        return null;
    }

    /**
     *
     * Establish connection to SugarCRM instance
     *
     * @return $this
     * @throws \Exception
     */
    public function connect()
    {
        return $this;
    }

    /**
     * Function: check()
     * Parameters:   none
     * Description:  Check if authenticated
     * Returns:  TRUE if authenticated, otherwise FALSE
     */
    public function check()
    {
        return (bool)$this->token;
    }

    /**
     * Function: setUsername()
     * Parameters:   $value = Username for the REST API User
     * Description:  Set $username
     * Returns:  returns $username
     */
    public function setUsername($value)
    {
        $this->config['username'] = $value;

        return $this;
    }

    /**
     * Function: setPassword()
     * Parameters:   none
     * Description:  Set $password
     * Returns:  returns $passwrd
     */
    public function setPassword($value)
    {
        $this->config['password'] = $value;

        return $this;
    }

    /**
     * Function: insert()
     * Parameters:   $module = Record Type
     *   $fields = Record field values
     * Description:  This method creates a new record of the specified type
     * Returns:  returns Array if successful, otherwise FALSE
     */
    public function insert($module, $fields)
    {
        $fake_data = $this->fieldsFakeList($module);

        return array_merge($fake_data, $fields);
    }

    /**
     * Function: filter()
     * Parameters:  $module - The module to work with
     *   $params = [
     *     q - Search the records by this parameter, if you don't have a full-text search engine enabled it will only search the name field of the records.  (Optional)
     *     filter - The filter expression. Filter expressions are explained below. Json String
     *     max_num - A maximum number of records to return Optional
     *     offset -  How many records to skip over before records are returned (Optional)
     *     fields -  Comma delimited list of what fields you want returned. The field date_modified will always be added  (Optional)
     *     order_by -  How to sort the returned records, in a comma delimited list with the direction appended to the column name after a colon. Example: last_name:DESC,first_name:DESC,date_modified:ASC (Optional)
     *     deleted - Show deleted records in addition to undeleted records (Optional)
     *   ]
     * Description:  Search records in this module
     * Returns:  returns Object if successful, otherwise FALSE
     */
    public function filter($module, $params = array())
    {
        if(!isset($params['max_num'])) {
            $params['max_num'] = 3;
        }

        $records = array();
        for($i = 0; $i < $params['max_num']; $i++) {
            $records[] = $this->fieldsFakeList($module);
        }

        $fake_data = array(
            'next_offset' => 2,
            'records'     => $records,
        );

        return $fake_data;
    }

    /**
     * Function: delete()
     * Parameters: $module = Record Type
     *   $record = The record to delete
     * Description:  This method deletes a record of the specified type
     * Returns:  returns Object if successful, otherwise FALSE
     */
    public function delete($module, $record)
    {
        return true;
    }

    /**
     * Function: retrieve()
     * Parameters: $module = Record Type
     *   $record = The record to retrieve
     * Description:  This method retrieves a record of the specified type
     * Returns:  Returns a single record
     */
    public function retrieve($module, $record, $options = array())
    {
        $fake_data = $this->fieldsFakeList($module);

        $fake_data['id'] = $record;

        return $fake_data;
    }

    /**
     * Function: update()
     * Parameters: $module = Record Type
     *   $fields = Record field values
     *   $fields['id'] = The record to update
     * Description:  This method updates a record of the specified type
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function update($module, $fields)
    {
        $result = false;
        if(isset($fields['id'])) {
            $fake_data = $this->fieldsFakeList($module);

            $result = array_merge($fake_data, $fields);
        }

        return $result;
    }

    /**
     * Function: favorite()
     * Parameters: $module = Record Type
     *   $record = The record to favorite
     * Description:  This method favorites a record of the specified type
     * Returns:  Returns TRUE if successful, otherwise FALSE
     */
    public function favorite($module, $record)
    {
        $fake_data = $this->fieldsFakeList($module);

        $fake_data['id']          = $record;
        $fake_data['my_favorite'] = true;

        return $fake_data;
    }

    /**
     * Function: unfavorite()
     * Parameters: $module = Record Type
     *   $record = The record to unfavorite
     * Description:  This method unfavorites a record of the specified type
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function unfavorite($module, $record)
    {
        $fake_data = $this->fieldsFakeList($module);

        $fake_data['id']          = $record;
        $fake_data['my_favorite'] = false;

        return $fake_data;
    }

    /**
     * Function: files()
     * Parameters: $module = Record Type
     *   $record = The record  we are working with
     * Description:  Gets a listing of files related to a field for a module record.
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function files($module, $record)
    {

    }

    /**
     * Function: download()
     * Parameters: $module = Record Type
     *   $record = The record  we are working with
     *   $field = Field associated to the file
     * Description:  Gets the contents of a single file related to a field for a module record.
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function download($module, $record, $field, $destination = null)
    {

    }

    /**
     * Function: upload()
     * Parameters: $module = Record Type
     *   $record = The record  we are working with
     *   $params = [
     *     format - sugar-html-json (Required),
     *     delete_if_fails - Boolean indicating whether the API is to mark related record deleted if the file upload fails.  Optional (if used oauth_token is also required)
     *     oauth_token - oauth_token_value Optional (Required if delete_if_fails is true)
     *   ]
     * Description:  Saves a file. The file can be a new file or a file override.
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function upload($module, $record, $field, $path, $params = array())
    {

    }

    /**
     * Function: deleteFile()
     * Parameters: $module = Record Type
     *   $record = The record  we are working with
     *   $field = Field associated to the file
     * Description:  Saves a file. The file can be a new file or a file override.
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function deleteFile($module, $record, $field)
    {

    }

    /**
     * Function: related()
     * Parameters: $module = Record Type
     *   $record = The record we are working with
     *   $link = The link for the relationship
     *   $limit = limit for the records returns (optional)
     * Description:  This method retrieves a list of records from the specified link
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function related($module, $record, $link, $options = array())
    {
        $records = array();
        for($i = 0; $i < 3; $i++) {
            $records[] = $this->fieldsFakeList(ucwords($link));
        }

        $fake_data = array(
            'next_offset' => -1,
            'records'     => $records,
        );

        return $fake_data;
    }

    /**
     * Function: relate()
     * Parameters: $module = Record Type
     *   $record = The record we are working with
     *   $link = The link for the relationship
     *   $related_record_id = the record to relate to
     *   $fields = Relationship data
     * Description:  This method relates 2 records
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function relate($module, $record, $link, $related_record_id, $fields = array())
    {
        $fake_data                         = $this->fieldsFakeList($module);
        $fake_data['related_record']       = $this->fieldsFakeList(ucwords($link));
        $fake_data['id']                   = $record;
        $fake_data['related_record']['id'] = $related_record_id;

        return $fake_data;
    }

    /**
     * Function: unrelate()
     * Parameters: $module = Record Type
     *   $record = The record to unfavorite
     * Description:  This method removes the relationship for 2 records
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function unrelate($module, $record, $link, $related_record)
    {
        $fake_data                         = $this->fieldsFakeList($module);
        $fake_data['related_record']       = $this->fieldsFakeList(ucwords($link));
        $fake_data['id']                   = $record;
        $fake_data['related_record']['id'] = $related_record;

        return $fake_data;
    }

    /**
     * Function: updateRelationship()
     * Parameters: $module = Record Type
     *   $record = The record we are working with
     *   $link = The link for the relationship
     *   $related_record = the record to relate to
     *   $fields = Relationship data
     * Description:  This method updates relationship data
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function updateRelationship($module, $record, $link, $related_record, $fields = array())
    {
        $fake_data                         = $this->fieldsFakeList($module);
        $fake_data['related_record']       = array_merge($this->fieldsFakeList(ucwords($link)), $fields);
        $fake_data['id']                   = $record;
        $fake_data['related_record']['id'] = $related_record;

        return $fake_data;
    }

    public function metadata()
    {

    }

    /**
     *
     * Get language file from the SugarCRM
     *
     * @param string $l
     *
     * @return array|bool
     */
    public function lang($l = 'en')
    {

    }

    /**
     *
     * @param        $what
     * @param string $method
     * @param array $data
     *
     * @return bool
     *
     */
    public function call($what, $method = 'GET', $data = array())
    {

    }

    /**
     * check connection
     *
     * @throws \Exception
     */
    protected function checkConnection()
    {

    }

    /**
     * get request result
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return mixed
     */
    protected function getRequestResult($method, $uri, $options = [])
    {

    }

    /**
     * add token to headers
     *
     * @param string|null $token
     * @return \Closure
     */
    protected function addToken($token = null)
    {

    }

    private function fieldsFakeList($module)
    {
        $fieldMetadata = new FieldMetadata();
        $metadata      = $fieldMetadata->findByModule($module);
        $fields        = array();
        foreach($metadata as $key => $item) {
            switch($item->field_type) {
                case'link':
                case'relate':
                case'worklog':
                    continue;
                    break;
                case'bool':
                    $fields[$item->field_name] = $this->faker->boolean(32);
                    break;
                case'decimal':
                    $fields[$item->field_name] = $this->faker->randomFloat(4);
                    break;
                case'date':
                    $fields[$item->field_name] = $this->faker->date();
                    break;
                case'datetime':
                case'datetimecombo':
                    $fields[$item->field_name] = $this->faker->iso8601;
                    break;
                case'email':
                    $fields[$item->field_name] = $this->faker->email;
                    break;
                case'enum':
                    $options                   = $item->options_list;
                    $fields[$item->field_name] = next($options);
                    break;
                case'phone':
                    $fields[$item->field_name] = $this->faker->phoneNumber;
                    break;
                case'id':
                    $fields[$item->field_name] = $this->faker->uuid;
                    break;
                case'int':
                    $fields[$item->field_name] = $this->faker->randomNumber(5);
                    break;
                case'name':
                case'fullname':
                    $fields[$item->field_name] = $this->faker->name;
                    break;
                case'text':
                case'html':
                    $fields[$item->field_name] = $this->faker->text;
                    break;
                case'varchar':
                case'user_name':
                case'assigned_user_name':
                    $fields[$item->field_name] = $this->faker->word;
                    break;
                case'url':
                    $fields[$item->field_name] = $this->faker->url;
                    break;
                default:
                    $fields[$item->field_name] = '';
            }
        }
        $fields['_module'] = $module;

        return $fields;
    }

}
