<?php namespace Sugarcrm\Bean\Api;
use GuzzleHttp\Client as Gclient;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;



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
     * Variable: $gclient
     * Description:  Guzzle Client
     */
    private $gclient;
    /**
     * Function: __construct()
     * Parameters:   none
     * Description:  Construct Class
     * Returns:  VOID
     */
    function __construct($config, $options = array())
    {
        $this->config = $config;
        // by default http issue will NOT throw exception. API uses headers and we may get headers other than 200
        $gclientOptions = ['base_uri' => $config['host']];
        if (array_key_exists('exceptions', $options)) {
            $gclientOptions['exceptions'] = $options['exceptions'];
            unset($options['exceptions']);
        } else {
            $gclientOptions['exceptions'] = false;
        }

        // if you need to pass extra headers to http, use 'headers' => ['Cookie'=>'value'] format
        if (array_key_exists('headers', $options)) {
            if (is_array($options['headers'])) {
                $gclientOptions['headers'] = $options['headers'];
            }
            unset($options['headers']);
        }

        // map rest of the options directly
        if (!empty($options)) {
            foreach ($options as $k => $v) {
                $gclientOptions[$k] = $v;
            }
        }
        // we need HandlerStack to add OAuth-Token to request headers when connection established
        $stack = new HandlerStack();
        $stack->setHandler(\GuzzleHttp\choose_handler());
        $gclientOptions['handler'] = $stack;

        $this->gclient = new Gclient($gclientOptions);
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
            $result = $this->getRequestResult('POST', 'oauth2/logout', ['headers' => ['OAuth-Token' => $this->token]]);
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
        $gresponse = $this->gclient->request('POST', 'oauth2/token',[
            'form_params' => [
                'grant_type'    => 'password',
                'client_id'     => $this->config['client_id'],
                'username'      => $this->config['username'],
                'password'      => $this->config['password'],
                "client_secret" => "",
                "platform"      => $this->config['platform'],
            ]
        ]);
        if ($gresponse->getStatusCode() >= 500) {
            throw new \Exception('SugarCRM API is not available');
        }
        $gresults = json_decode((string) $gresponse->getBody(), true);

        if (!$gresults['access_token']) {
            if (array_key_exists('error_message', $gresults)) {
                throw new \Exception('Unable to connect to SugarCRM: ' . $gresults['$results']);
            } else {
                throw new \Exception('Unable to connect to SugarCRM');
            }
        }

        $token = $this->token = $gresults['access_token'];
        // add Middleware function to add token to headers
        $this->gclient->getConfig('handler')->push($this->addToken($token));

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
        return (bool) $this->token;
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
        return $this->getRequestResult('POST', $module, ['form_params' => $fields]);
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
        return $this->getRequestResult('GET', $module, ['json' => $params]);
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
        $this->checkConnection();
        $gresponse = $this->gclient->request('DELETE', $module . '/' . $record);
        if ($gresponse->getStatusCode() != 200) {
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
        $urlOptions = array();

        if (isset($options['fields'])) {
            if (is_array($options['fields'])) {
                $options['fields'] = implode(',', $options['fields']);
            }
            $urlOptions[] = 'fields=' . $options['fields'];
        }

        return $this->getRequestResult('GET', $module . '/' . $record . '?' . implode('&', $urlOptions));
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
        if (isset($fields['id'])) {
            $record = $fields['id'];
            unset($fields['id']);

            $result = $this->getRequestResult('PUT', $module . '/' . $record, ['json' => $fields]);
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
        return $this->getRequestResult('PUT', $module . '/' . $record . '/favorite');
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
        return $this->getRequestResult('DELETE', $module . '/' . $record . '/favorite');
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
        return $this->getRequestResult('GET', $module . '/' . $record . '/file');
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
        $this->checkConnection();

        $options = [];
        if (is_string($destination)) {
            $resource = fopen($destination, 'w+');
            $options['sink'] = $resource;
        }

        $result = $this->gclient->request('GET', $module . '/' . $record . '/file/' . $field, $options);
        return !$result ? false : $result;
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
        $this->checkConnection();

        // we must add oauth_token if delete_if_fails is true
        if (isset($params['delete_if_fails']) && $params['delete_if_fails']) {
            $params["oauth_token"] = $this->token;
        }

        $body = fopen($path, 'r');
        $uri = $module . '/' . $record . '/file/' . $field;
        $gresponse = $this->gclient->request('PUT', $uri, ['body' => $body, 'query' => $params]);

        error_log("Response Content Type: " . $gresponse->getHeader('Content-Type')[0]);
        $result = json_decode(html_entity_decode($gresponse->getBody(true)), true);
        return !$result ? false : $result;
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
        return $this->getRequestResult('DELETE', $module . '/' . $record . '/file/' . $field);
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

        return $this->getRequestResult('GET', $url);
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
        return $this->getRequestResult(
            'POST',
            $module . '/' . $record . '/link/' . $link . '/' . $related_record_id,
            ['json' => $fields]
        );
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
        return $this->getRequestResult('DELETE', $module . '/' . $record . '/link/' . $link . '/' . $related_record);
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
        return $this->getRequestResult(
            'PUT',
            $module . '/' . $record . '/link/' . $link . '/' . $related_record,
            ['json' => $fields]
        );
    }

    public function metadata()
    {
        return $this->getRequestResult('GET', 'metadata');
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
        return $this->getRequestResult('GET', 'lang/' . $l);
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
        return $this->getRequestResult($method, $what, ['json' => $data]);
    }

    /**
     * check connection
     *
     * @throws \Exception
     */
    protected function checkConnection()
    {
        if (!$this->check()) {
            $this->connect();
        }
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
        $this->checkConnection();
        $gresponse = $this->gclient->request($method, $uri, $options)->getBody();
        $result = json_decode((string) $gresponse, true);
        return ($result && !isset($result['error'])) ? $result : false;

    }

    /**
     * add token to headers
     *
     * @param string|null $token
     * @return \Closure
     */
    protected function addToken($token = null)
    {
        return function (callable $handler) use ($token) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $token) {
                $request = $token ? $request->withHeader('OAuth-Token', $token) : $request;
                return $handler($request, $options);
            };
        };
    }

}
