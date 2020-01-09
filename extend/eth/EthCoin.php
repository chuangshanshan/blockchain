<?php

namespace eth;

class EthCoin
{
    // Configuration options
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    private $CACertificate;

    // Information and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;

    private $id = 0;
    public static $instance = null;

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $proto
     * @param string $url
     */
    protected function __construct($url = null)
    {
        $this->username = config('eth.username');
        $this->password = config('eth.password');;
        $this->host = config('eth.host');;
        $this->port = config('eth.port');;
        $this->url = $url;

        // Set some defaults
        $this->proto = 'http';
        $this->CACertificate = null;
    }

    public static function getInstance($url = null)
    {
        return self::$instance && self::$instance->url == $url ? self::$instance : new self($url);
    }

    /**
     * @param string|null $certificate
     */
    function setSSL($certificate = null)
    {
        $this->proto = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }

    function __call($method, $params)
    {
        $this->status = null;
        $this->error = null;
        $this->raw_response = null;
        $this->response = null;

        // If no parameters are passed, this will be an empty array
        $params = array_values($params);
        // The ID should be unique for each call
        $this->id++;

        // Build the request, it's ok that params might have any empty array
        $request = json_encode(
            array(
                'method' => $method,
                'params' => $params,
                'id' => $this->id
            )
        );
        //echo $request;
        // Build the cURL session
        $curl = curl_init(
            "{$this->proto}://{$this->username}:{$this->password}@{$this->host}:{$this->port}/{$this->url}"
        );
//         $curl    =curl_init("{$this->proto}://@{$this->host}:{$this->port}/{$this->url}");
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 3000,
            CURLOPT_HTTPHEADER => array('Content-type: application/json'),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request
        );

        // This prevents users from getting the following warning when open_basedir is set:
        // Warning: curl_setopt() [function.curl-setopt]: CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }

        if ($this->proto == 'https') {
            // If the CA Certificate was specified we change CURL to look for it
            if ($this->CACertificate != null) {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            } else {
                // If not we need to assume the SSL cannot be verified so we set this flag to FALSE to allow the connection
                $options[CURLOPT_SSL_VERIFYPEER] = false;
            }
        }

        curl_setopt_array($curl, $options);

        // Execute the request and decode to an array
        $this->raw_response = curl_exec($curl);
        $this->response = json_decode($this->raw_response, true);

        // If the status is not 200, something is wrong
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // If there was no error, this will be an empty string
        $curl_error = curl_error($curl);

        curl_close($curl);

        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }

        if (isset($this->response['error']) && $this->response['error']) {
            // If bitcoind returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        } elseif ($this->status != 200) {
            // If bitcoind didn't return a nice error message, we need to make our own
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }

        if ($this->error) {
            return false;
        }

        return $this->response['result'];
    }

}