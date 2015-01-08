<?php namespace Sugarcrm\Bean\Api;

use Guzzle\Http\Client;
use Guzzle\Common\Event;
use Guzzle\Http\Query;

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

    /**
     * Variable: $client
     * Description:  Guzzle Client
     */
    private $client;

    /**
     * Function: __construct()
     * Parameters:   none
     * Description:  Construct Class
     * Returns:  VOID
     */
    function __construct($config, $options = array())
    {
        $this->config = $config;
        $this->client = new Client();
        $this->client->setBaseUrl($config['host']);

        // by default http issue will NOT through exception. API uses headers and we may get headers other than 200
        if (array_key_exists('exceptions', $options)) {
            $this->client->setDefaultOption('exceptions', $options['exceptions']);
            unset($options['exceptions']);
        } else {
            $this->client->setDefaultOption('exceptions', false);
        }


        // if you need to pass extra headers to http, use 'headers' => ['Cookie'=>'value'] format
        if (array_key_exists('headers', $options)) {
            if (is_array($options['headers'])) {
                foreach ($options['headers'] as $k => $v) {
                    $this->client->setDefaultOption('headers/' . $k, $v);
                }
            }
            unset($options['headers']);
        }

        // map rest of the options directly
        if (!empty($options)) {
            foreach ($options as $k => $v) {
                $this->client->setDefaultOption($k, $v);
            }
        }
    }

    /**
     * Function: __destruct()
     * Parameters:   none
     * Description:  OAuth2 Logout
     * Returns:  TRUE on success, otherwise FALSE
     */
    function __destruct()
    {
        if (!empty($this->token)) {
            $request = $this->client->post('oauth2/logout');
            $request->setHeader('OAuth-Token', $this->token);
            $result = $request->send()->json();

            return $result;
        }

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
        $request = $this->client->post('oauth2/token', null, array(
            'grant_type'    => 'password',
            'client_id'     => $this->config['client_id'],
            'username'      => $this->config['username'],
            'password'      => $this->config['password'],
            "client_secret" => "",
            "platform"      => $this->config['platform'],
        ));

        $response = $request->send();

        if ($response->getStatusCode() >= 500) {
            throw new \Exception('SugarCRM API is not available');
        }

        $results = $response->json();

        if (!$results['access_token']) {
            if (array_key_exists('error_message', $results)) {
                throw new \Exception('Unable to connect to SugarCRM: ' . $results['$results']);
            } else {
                throw new \Exception('Unable to connect to SugarCRM');
            }
        }

        $token = $this->token = $results['access_token'];

        $this->client->getEventDispatcher()->addListener('request.before_send', function (Event $event) use ($token) {
            $event['request']->setHeader('OAuth-Token', $token);
        });

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
        if (!$this->token) {
            return false;
        }

        return true;
    }

    /**
     * Function: setUrl()
     * Parameters:   $value = URL for the REST API
     * Description:  Set $url
     * Returns:  returns $url
     */
    public function setUrl($value)
    {
        $this->client->setBaseUrl($value);

        return $this;
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
     * Function: create()
     * Parameters:   $module = Record Type
     *   $fields = Record field values
     * Description:  This method creates a new record of the specified type
     * Returns:  returns Array if successful, otherwise FALSE
     */
    public function create($module, $fields)
    {
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->post($module, null, $fields);
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
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
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->get($module);

        $query = $request->getQuery();
        foreach ($params as $key => $value) {
            if ($key == 'filter' && is_array($value)) {
                $value = json_encode($value);
            }
            $query->add($key, $value);
        }
        echo '<pre>';
        print_r($request->getQuery());
        echo '</pre>';

        $result = $request->send()->json();

        if (!$result) {
            return null;
        }

        return $result;
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
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->delete($module . '/' . $record);
        $response = $request->send();
//$result  = $response->json(); // could be used to get actual error

        if ($response->getStatusCode() != 200) {
            return false;
        }

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
        if (!$this->check()) {
            $this->connect();
        }

        $urlOptions = array();

        if (isset($options['fields'])) {
            if (is_array($options['fields'])) {
                $options['fields'] = implode(',', $options['fields']);
            }
            $urlOptions[] = 'fields=' . $options['fields'];
        }

        $request = $this->client->get($module . '/' . $record . '?' . implode('&', $urlOptions));

        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     * Function: update()
     * Parameters: $module = Record Type
     *   $record = The record to update
     *   $fields = Record field values
     * Description:  This method updates a record of the specified type
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function update($module, $record, $fields)
    {
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->put($module . '/' . $record, null, json_encode($fields));
        $result = $request->send()->json();

        if (!$result) {
            return false;
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
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->put($module . '/' . $record . '/favorite');
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
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
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->delete($module . '/' . $record . '/favorite');
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
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
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->get($module . '/' . $record . '/file');
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     * Function: download()
     * Parameters: $module = Record Type
     *   $record = The record  we are working with
     *   $field = Field associated to the file
     * Description:  Gets the contents of a single file related to a field for a module record.
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function download($module, $record, $field, $destination)
    {
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->get($module . '/' . $record . '/file/' . $field);
        $request->setResponseBody($destination);
        $result = $request->send();

        if (!$result) {
            return false;
        }

        return $result;
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
        if (!$this->check()) {
            $this->connect();
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $path);
        finfo_close($finfo);

        $request = $this->client->put($module . '/' . $record . '/file/' . $field, array(), fopen($path, 'r'),
            array('query' => $params));
        $request->setHeader('Content-Type', $contentType);
        $result = $request->send();

        error_log("Response Content Type: " . $result->getContentType());

        $r = json_decode(html_entity_decode($result->getBody(true)), true);
        if (!$r) {
            return false;
        }

        return $r;
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
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->delete($module . '/' . $record . '/file/' . $field);
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
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
        if (!$this->check()) {
            $this->connect();
        }

        $url = $module . '/' . $record . '/link/' . $link;
        if (!empty($options)) {
            $urlOptions = array();

            if (isset($options['limit'])) {
                $urlOptions[] = 'max_num=' . $options['limit'];
            }

            if (isset($options['offset'])) {
                $urlOptions[] = 'offset=' . $options['offset'];
            }

            if (isset($options['order_by'])) {
                if (is_array($options['order_by'])) {
                    $options['order_by'] = implode(',', $options['order_by']);
                }
                $urlOptions[] = 'order_by=' . $options['order_by'];
            }

            if (isset($options['fields'])) {
                if (is_array($options['fields'])) {
                    $options['fields'] = implode(',', $options['fields']);
                }
                $urlOptions[] = 'fields=' . $options['fields'];
            }

            if (isset($options['filter'])) {
                $url .= '/filter';
                if (is_array($options['filter'])) {
                    $options['filter'] = json_encode($options['filter']);
                }
                $urlOptions[] = 'filter=' . urlencode($options['filter']);
            }
            $url .= '?' . join('&', $urlOptions);
        }

        $request = $this->client->get($url);
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     * Function: relate()
     * Parameters: $module = Record Type
     *   $record = The record we are working with
     *   $link = The link for the relationship
     *   $related_record = the record to relate to
     *   $fields = Relationship data
     * Description:  This method relates 2 records
     * Returns:  Returns an Array if successful, otherwise FALSE
     */
    public function relate($module, $record, $link, $related_record, $fields = array())
    {
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->post($module . '/' . $record . '/link/' . $link . '/' . $related_record, array(),
            $fields);
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
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
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->delete($module . '/' . $record . '/link/' . $link . '/' . $related_record);
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
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
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->put($module . '/' . $record . '/link/' . $link . '/' . $related_record, array(),
            json_encode($fields));
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
    }

    public function metadata()
    {
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->get('metadata');
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     *
     * Get language file form the SugarCRM
     *
     * @param string $l
     *
     * @return array|bool
     */
    public function lang($l = 'en')
    {
        if (!$this->check()) {
            $this->connect();
        }

        $request = $this->client->get('lang/' . $l);
        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     *
     * DO NOT USE!!! Have not even tried.
     *
     * @param        $what
     * @param string $method
     * @param array $data
     *
     * @return bool
     *
     */
    public function call($what, $method = 'get', $data = array())
    {
        if (!$this->check()) {
            $this->connect();
        }
        $method = strtolower($method);

        $request = $this->client->$method($what, json_encode($data));

        $result = $request->send()->json();

        if (!$result) {
            return false;
        }

        return $result;
    }
}
