<?php

date_default_timezone_set('America/Los_Angeles');

define('DISQUS_API_URL', 'http://dev.disqus.org:9000/api/');

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../disqusapi.php');

class DisqusAPITest extends PHPUnit_Framework_TestCase {
    private $secret = 'a';
    
    function test_setKey() {
        $api = new DisqusAPI('a');
        $this->assertEquals($api->key, 'a');
        $api->setKey('b');
        $this->assertEquals($api->key, 'b');
    }

    function test_setFormat() {
        $api = new DisqusAPI();
        $this->assertEquals($api->format, 'json');
        $api->setFormat('jsonp');
        $this->assertEquals($api->format, 'jsonp');
    }
    
    function test_setVersion() {
        $api = new DisqusAPI();
        $this->assertEquals($api->version, '3.0');
        $api->setVersion('3.1');
        $this->assertEquals($api->version, '3.1');
    }
    
    /**
     * @expectedException DisqusInterfaceNotDefined
     */
    function test_invalid_function() {
        $api = new DisqusAPI();
        $api->users->foo(array($foo='bar'));
    }
    
    /**
     * @expectedException APIError
     */
    function test_users_listActivity() {
        $api = new DisqusAPI($this->secret);
        $api->users->listActivity(array($foo='bar'));
    }
}

?>