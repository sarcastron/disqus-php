<?php
/**
 * Implementation of the Disqus API.
 *
 * http://disqus.com/api/
 *
 * @author		DISQUS <team@disqus.com>
 * @copyright	2007-2010 Big Head Labs
 * @link		http://disqus.com/
 * @package		disqusapi
 * @version		1.1
 * 
 * $disqus = new DisqusAPI($secret_key)
 * $disqus->trends->listThreads()
 * 
 */

if (!defined('DISQUS_API_HOST')) {
    define('DISQUS_API_HOST', 'disqus.com');
}
if (!defined('DISQUS_API_SSL_HOST')) {
    define('DISQUS_API_SSL_HOST', 'secure.disqus.com');
}
define('DISQUS_API_VERSION', '0.0.1');

require_once(dirname(__FILE__) . '/url.php');

if (!extension_loaded('json')) {
	require_once(dirname(__FILE__) . '/json.php');
	function dsq_json_decode($data) {
		$json = new JSON;
		return $json->unserialize($data);
	}
} else {
	function dsq_json_decode($data) {
		return json_decode($data);
	}	
}

$DISQUS_API_INTERFACES = dsq_json_decode(file_get_contents(dirname(__FILE__) . '/interfaces.json'));

class DisqusInterfaceNotDefined extends Exception {}
class DisqusAPIError extends Exception {
    public function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }
}

class DisqusResource {
    public function __construct($api, $interface=null, $node=null, $tree=array()) {
        if (!$interface) {
            global $DISQUS_API_INTERFACES;
            $interface = $DISQUS_API_INTERFACES;
        }
        $this->api = $api;
        $this->interface = $interface;
        $this->node = $node;
        if ($node) {
            array_push($tree, $node);
        }
        $this->tree = $tree;
    }
    
    public function __get($attr) {
        if (!array_key_exists($attr, $this->interface)) {
            throw new DisqusInterfaceNotDefined();
        }
        return new DisqusResource($this->api, $this->interface->$attr, $attr, $this->tree);
    }
    
    public function __invoke($kwargs=array()) {
        $resource = $this->interface;
        foreach ($resource->required as $k) {
            if (empty($kwargs[$k])) {
                throw new Exception('Missing required argument: '.$k);
            }
        }
        
        $api = $this->api;
        
        if (!empty($kwargs['format'])) {
            $kwargs['format'] = $api->format;
        }
        if (!empty($kwargs['api_secret'])) {
            $kwargs['api_secret'] = $api->key;
        }
        
        // emulate a named pop
        $version = (!empty($kwargs['version']) ? $kwargs['version'] : $api->version);
        unset($kwargs['version']);
        
        $url = ($api->is_secure ? 'http://'.DISQUS_API_HOST : 'https://'.DISQUS_API_SSL_HOST);
        $path = '/api/'.$version.implode('/', $this->tree);
        
        if ($resource->method == 'POST') {
            $post_data = $kwargs;
        } else {
            $post_data = false;
            $path .= '?'.dsq_get_query_string($kwargs);
        }
        
        $response = dsq_urlopen($url.$path, $postdata);
        
        $data = call_user_func($api->formats[$kwargs['format']], $response['data']);
        
        if ($response['code'] != 200) {
            throw new DisqusAPIError($data['code'], $data['response']);
        }
        
        return $data['response'];
    }
}


class DisqusAPI extends DisqusResource {
    private static $formats = array(
        'json' => 'dsq_json_decode'
    );

    public function __construct($key=null, $format='json', $version='3.0', $is_secure=false) {
        $this->key = $key;
        $this->format = $format;
        $this->version = $version;
        $this->is_secure = $is_secure;
        parent::__construct($this);
    }

    public function __invoke() {
        throw new Exception('You cannot call the API without a resource.');
    }

    public function setKey($key) {
        $this->key = $key;
    }

    public function setFormat($format) {
        $this->format = $format;
    }
    
    public function setVersion($version) {
        $this->version = $version;
    }
}