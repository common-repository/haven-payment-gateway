<?php
/**
 * haven_wallet_rpc
 *
 * Written using the JSON RPC specification -
 * http://json-rpc.org/wiki/specification
 *
 * @author Kacper Rowinski <krowinski@implix.com>
 * Modified to remove curl by Blueyred
 * Modified to work with haven-rpc wallet by Serhack and cryptochangements
 * Modified to work with haven-wallet-rpc wallet by mosu-forge
 */

defined( 'ABSPATH' ) || exit;

class Haven_Wallet_Rpc
{
    protected $url = null, $is_debug = false;
    protected $request_timeout = 8;
    protected $host;
    protected $port;
    private $httpErrors = array(
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        408 => '408 Request Timeout',
        500 => '500 Internal Server Error',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable'
    );

    public function __construct($pHost, $pPort)
    {

        $this->validate(false === extension_loaded('json'), 'The json extension must be loaded to use this class!');

        $this->host = $pHost;
        $this->port = $pPort;
        $this->url = $pHost . ':' . $pPort . '/json_rpc';
    }

    public function validate($pFailed, $pErrMsg)
    {
        if ($pFailed) {
            if(is_admin()) echo $pErrMsg;
        }
    }

    public function setDebug($pIsDebug)
    {
        $this->is_debug = !empty($pIsDebug);
        return $this;
    }

    public function _print($json)
    {
        $json_encoded = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if(is_admin()) echo $json_encoded;
    }

    public function _run($method, $params = null)
    {
        $result = $this->request($method, $params);
        return $result; //the result is returned as an array
    }

    private function request($pMethod, $pParams)
    {
        static $requestId = 0;

        // generating uniuqe id per process
        $requestId++;

        // check if given params are correct
        $this->validate(false === is_scalar($pMethod), 'Method name has no scalar value');

        // Request (method invocation)
        $request = json_encode(array('jsonrpc' => '2.0', 'method' => $pMethod, 'params' => $pParams, 'id' => $requestId));

        // if is_debug mode is true then add url and request to is_debug
        $this->debug('Url: ' . $this->url . "\r\n", false);
        $this->debug('Request: ' . $request . "\r\n", false);

        // Response (method invocation)
        $responseMessage = $this->getResponse($request);

        // if is_debug mode is true then add response to is_debug and display it
        $this->debug('Response: ' . $responseMessage . "\r\n", true);

        // decode and create array ( can be object, just set to false )
        $responseDecoded = json_decode($responseMessage, true);

        // check if decoding json generated any errors
        $jsonErrorMsg = $this->getJsonLastErrorMsg();
        $this->validate(!is_null($jsonErrorMsg), $jsonErrorMsg . ': ' . $responseMessage);
        // check if response is correct
        $this->validate(empty($responseDecoded['id']), 'Invalid response data structure: ' . $responseMessage);
        $this->validate($responseDecoded['id'] != $requestId, 'Request id: ' . $requestId . ' is different from Response id: ' . $responseDecoded['id']);
        if (isset($responseDecoded['error'])) {
            $errorMessage = 'Request have return error: ' . $responseDecoded['error']['message'] . '; ' . "\n" .
                'Request: ' . $request . '; ';
            if (isset($responseDecoded['error']['data'])) {
                $errorMessage .= "\n" . 'Error data: ' . $responseDecoded['error']['data'];
            }
            $this->validate(!is_null($responseDecoded['error']), $errorMessage);
        }
        return $responseDecoded['result'];
    }

    protected function debug($pAdd, $pShow = false)
    {
        static $debug, $startTime;
        // is_debug off return
        if (false === $this->is_debug) {
            return;
        }
        // add
        $debug .= $pAdd;
        // get starttime
        $startTime = empty($startTime) ? array_sum(explode(' ', microtime())) : $startTime;
        if (true === $pShow and !empty($debug)) {
            // get endtime
            $endTime = array_sum(explode(' ', microtime()));
            // performance summary
            $debug .= 'Request time: ' . round($endTime - $startTime, 3) . ' s Memory usage: ' . round(memory_get_usage() / 1024) . " kb\r\n";
            if(is_admin()) echo nl2br($debug);
            // send output immediately
            flush();
            // clean static
            $debug = $startTime = null;
        }
    }

    protected function & getResponse(&$pRequest)
    {   
        
        $post_args = array(
                'method' => 'POST',
                'headers' => array('Content-type: application/json'),
                'body' => $pRequest,
                'sslverify' => false,
                'timeout' => $this->request_timeout,
                'compress' => true,  
                );
                
        $response = wp_remote_post($this->url, $post_args);
        
        $http_code = wp_remote_retrieve_response_code($response);
        
        if (isset($this->httpErrors[$http_code])) {
            if(is_admin())
                echo 'Response Http Error - ' . $this->httpErrors[$http_code];
        }

        if ( is_wp_error($response) ) {
            if(is_admin()){
                $error_message = $response->get_error_message();
                echo '[ERROR] Failed to connect to haven-wallet-rpc at ' . $this->host . ' port '. $this->port .'</br>';
                echo '[MSG] ' . $error_message .'</br>';
            }
        }
        
        $response_body = wp_remote_retrieve_body($response);
                
        return $response_body;
    }

    //prints result as json

    function getJsonLastErrorMsg()
    {
        if (!function_exists('json_last_error_msg')) {
            function json_last_error_msg()
            {
                static $errors = array(
                    JSON_ERROR_NONE => 'No error',
                    JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
                    JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
                    JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
                    JSON_ERROR_SYNTAX => 'Syntax error',
                    JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
                );
                $error = json_last_error();
                return array_key_exists($error, $errors) ? $errors[$error] : 'Unknown error (' . $error . ')';
            }
        }

        // Fix PHP 5.2 error caused by missing json_last_error function
        if (function_exists('json_last_error')) {
            return json_last_error() ? json_last_error_msg() : null;
        } else {
            return null;
        }
    }

    /*
     * The following functions can all be called to interact with the Haven RPC wallet
     * They will majority of them will return the result as an array
     * Example: $daemon->address(); where $daemon is an instance of this class, will return the wallet address as string within an array
     */

    public function address()
    {
        $address = $this->_run('getaddress');
        return $address;
    }

    public function getbalance()
    {
        $balance = $this->_run('getbalance');
        return $balance;
    }

    public function getheight()
    {
        $height = $this->_run('getheight');
        return $height['height'];
    }

    public function incoming_transfer($type)
    {
        $incoming_parameters = array('transfer_type' => $type);
        $incoming_transfers = $this->_run('incoming_transfers', $incoming_parameters);
        return $incoming_transfers;
    }

    public function view_key()
    {
        $query_key = array('key_type' => 'view_key');
        $query_key_method = $this->_run('query_key', $query_key);
        return $query_key_method;
    }

    public function make_integrated_address($payment_id)
    {
        $integrate_address_parameters = array('payment_id' => $payment_id);
        $integrate_address_method = $this->_run('make_integrated_address', $integrate_address_parameters);
        return $integrate_address_method;
    }

    /* A payment id can be passed as a string
       A random payment id will be generated if one is not given */

    public function split_integrated_address($integrated_address)
    {
        if (!isset($integrated_address)) {
            if(is_admin()) echo "Error: Integrated_Address must not be null";
        } else {
            $split_params = array('integrated_address' => $integrated_address);
            $split_methods = $this->_run('split_integrated_address', $split_params);
            return $split_methods;
        }
    }

    public function make_uri($address, $amount, $recipient_name = null, $description = null)
    {
        // Convert to atomic units
        $new_amount = $amount * HAVEN_GATEWAY_ATOMIC_UNITS_POW;

        $uri_params = array('address' => $address, 'amount' => $new_amount, 'payment_id' => '', 'recipient_name' => $recipient_name, 'tx_description' => $description);
        $uri = $this->_run('make_uri', $uri_params);
        return $uri;
    }

    public function parse_uri($uri)
    {
        $uri_parameters = array('uri' => $uri);
        $parsed_uri = $this->_run('parse_uri', $uri_parameters);
        return $parsed_uri;
    }

    public function transfer($amount, $address, $mixin = 12)
    {
        $new_amount = $amount * HAVEN_GATEWAY_ATOMIC_UNITS_POW;
        $destinations = array('amount' => $new_amount, 'address' => $address);
        $transfer_parameters = array('destinations' => array($destinations), 'mixin' => $mixin, 'get_tx_key' => true, 'unlock_time' => 0, 'payment_id' => '');
        $transfer_method = $this->_run('transfer', $transfer_parameters);
        return $transfer_method;
    }

    public function get_payments($payment_id)
    {
        $get_payments_parameters = array('payment_id' => $payment_id);
        $get_payments = $this->_run('get_payments', $get_payments_parameters);
        if(isset($get_payments['payments']))
            return $get_payments['payments'];
        else
            return array();
    }

    public function get_pool_payments($payment_id)
    {
        $get_payments_parameters = array('pool' => true);
        $get_payments = $this->_run('get_transfers', $get_payments_parameters);

        if(!isset($get_payments['pool']))
            return array();

        $payments = array();
        foreach($get_payments['pool'] as $payment) {
            if($payment['double_spend_seen'])continue;
            if($payment['payment_id'] == $payment_id) {
                $payment['tx_hash'] = $payment['txid'];
                $payment['block_height'] = $payment['height'];
                $payments[] = $payment;
            }
        }

        return $payments;
    }

    public function get_all_payments($payment_id)
    {
        $confirmed_payments = $this->get_payments($payment_id);
        $pool_payments = $this->get_pool_payments($payment_id);
        return array_merge($pool_payments, $confirmed_payments);
    }

    public function get_bulk_payments($payment_id, $min_block_height)
    {
        $get_bulk_payments_parameters = array('payment_id' => $payment_id, 'min_block_height' => $min_block_height);
        $get_bulk_payments = $this->_run('get_bulk_payments', $get_bulk_payments_parameters);
        return $get_bulk_payments;
    }

    public function get_transfers($arr)
    {
        $get_parameters = $arr;
        $get_transfers = $this->_run('get_transfers', $get_parameters);
        return $get_transfers;
    }

    public function get_address_index($subaddress)
    {
        $params = array('address' => $subaddress);
        return $this->_run('get_address_index', $params);
    }

    public function store()
    {
        return $this->_run('store');
    }

    public function create_address($account_index = 0, $label = '')
    {
        $params = array('account_index' => $account_index, 'label' => $label);
        $create_address_method = $this->_run('create_address', $params);
        $save = $this->store(); // Save wallet state after subaddress creation
        return $create_address_method;
    }
}
