<?php

class NMA_API
{

    /**
     * @const LIB_ERROR_TYPE can be exception or error
     */
    const LIB_ERROR_TYPE = 'error';

    /**
     * @const holds the api key verify url
     */
    const LIB_NMA_VERIFY = 'https://www.notifymyandroid.com/publicapi/verify';

    /**
     * @const holds the notify url
     */
    const LIB_NMA_NOTIFY = 'https://www.notifymyandroid.com/publicapi/notify';

    /**
     * toggles on debugging
     *
     * @var bool
     */
    public $debug = false;

    public $apiCallsRemaining = false;

    public $apiLimitReset = false;

    public $lastStatus = false;
    /**
     * @var bool|string
     */
    protected $apiKey = false;

    /**
     * @var bool|string
     */
    protected $devKey = false;


    protected $error_codes
        = array(
            200     => 'Notification submitted.',
            400     => 'The data supplied is in the wrong format, invalid length or null.',
            401     => 'None of the API keys provided were valid.',
            402     => 'Maximum number of API calls per hour exceeded.',
            500     => 'Internal server error. Please contact our support if the problem persists.'
        );

    /**
     * @param array $options
     */
    function __construct($options = array())
    {
        if (!isset($options['apikey'])) {
            return $this->error('You must supply a API Key');
        } else {
            $this->apiKey = $options['apikey'];
        }

        if (isset($options['developerkey'])) {
            $this->devKey = $options['developerkey'];
        }

        if (isset($options['debug'])) {
            $this->debug = true;
        }

        return true; // this shuts my ide up

    }


    /**
     * @param bool $key [optional] if not set the one used on construct is used
     *
     * @return bool|mixed|SimpleXMLElement|string
     */
    public function verify($key = false)
    {

        $options = array();

        if ($key !== false) {
            $options['apikey'] = $key;
        } else {
            $options['apikey'] = $this->apiKey;
        }


        if ($this->devKey) {
            $options['developerkey'] = $this->devKey;
        }

        return $this->makeApiCall(self::LIB_NMA_VERIFY, $options);
    }

    /**
     * @param string $application
     * @param string $event
     * @param string $description
     * @param string $url
     * @param int    $priority
     * @param bool   $apiKeys
     *
     * @return bool|mixed|SimpleXMLElement|string
     */
    public function notify($application = '', $event = '', $description = '', $url = '', $priority = 0, $apiKeys = false)
    {
        if (empty($application) || empty($event) || empty($description)) {
            return $this->error('you must supply a application name, event and long desc');
        }

        $post = array('application' => substr($application, 0, 256),
                      'event'       => substr($event, 0, 1000),
                      'description' => substr($description, 0, 10000),
                      'priority'    => $priority
        );
		if (!empty($url)) {
			$post['url'] = substr($url, 0, 2000);
		}
        if ($this->devKey) {
            $post['developerkey'] = $this->devKey;
        }

        if ($apiKeys !== false) {
            $post['apikey'] = $apiKeys;
        } else {
            $post['apikey'] = $this->apiKey;
        }

        return $this->makeApiCall(self::LIB_NMA_NOTIFY, $post, 'POST');
    }


    /**
     * @param        $url
     * @param null   $params
     * @param string $verb
     * @param string $format
     *
     * @return bool|mixed|SimpleXMLElement|string
     * @throws Exception
     */
    protected function makeApiCall($url, $params = null, $verb = 'GET', $format = 'xml')
    {
        $cparams = array(
            'http' => array(
                'method'        => $verb,
                'ignore_errors' => true
            )
        );
        if ($params !== null && !empty($params)) {
            $params = http_build_query($params);
            if ($verb == 'POST') {
                $cparams["http"]['header'] = 'Content-Type: application/x-www-form-urlencoded';
                $cparams['http']['content'] = $params;
            } else {
                $url .= '?' . $params;
            }
        } else {
            return $this->error(
                'this api requires all calls to have params' . $this->debug ? ', you provided: ' . var_dump($params)
                    : ''
            );
        }

        $context = stream_context_create($cparams);
        $fp = fopen($url, 'rb', false, $context);
        if (!$fp) {
            $res = false;
        } else {

            if ($this->debug) {
                $meta = stream_get_meta_data($fp);
                $this->error('var dump of http headers' . var_dump($meta['wrapper_data']));
            }

            $res = stream_get_contents($fp);
        }

        if ($res === false) {
            return $this->error("$verb $url failed: $php_errormsg");
        }

        switch ($format) {
            case 'json':
                return $this->error('this api does not support json');
            /*
            * uncomment the below if json is added later
            * $r = json_decode($res);
           if ($r === null) {
               return $this->error("failed to decode $res as json");
           }
           return $r;*/

            case 'xml':
                $r = simplexml_load_string($res);
                if ($r === null) {
                    return $this->error("failed to decode $res as xml");
                }
                return $this->process_xml_return($r);
        }
        return $res;
    }

    /**
     * @param     $message
     * @param int $type
     *
     * @return bool
     * @throws Exception
     */
    private function error($message, $type = E_USER_NOTICE)
    {
        if (self::LIB_ERROR_TYPE == 'error') {
            trigger_error($message, $type);
            return false;
        } else {
            throw new Exception($message, $type);
        }
    }

    /**
     * @param SimpleXMLElement $obj
     *
     * @return bool
     */
    private function process_xml_return(SimpleXMLElement $obj)
    {

        if (isset($obj->success)) {
            $this->lastStatus = $obj->success["@attributes"]['code'];

            $this->apiCallsRemaining = $obj->success["@attributes"]['remaining'];
            $this->apiLimitReset = $obj->success["@attributes"]['resettimer'];
            return true;
        } elseif (isset($obj->error)) {
            if (isset($obj->error["@attributes"])) {
                $this->lastStatus = $obj->error["@attributes"]['code'];

                if (isset($obj->error["@attributes"]['resettimer'])) {
                    $this->apiLimitReset = $obj->error["@attributes"]['resettimer'];
                }

            }
            return $this->error($obj->error);
        } else {
            return $this->error("unkown error");
        }
    }

}
