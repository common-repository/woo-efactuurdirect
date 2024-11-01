<?php
/**
 * Efd Api Gateway Toolkit
 * @uses Zend
 * @package Toolkit
 * @version 3.18.1
 * @copyright  Copyright (c) 2017
 */

class EfdApiToolkitGateway
{
    protected $_apiVer = '1.1.0';
    protected $_subdomain;
	protected $_login;
	protected $_key;
	protected $_apiUrl;

	/**
    * __construct
    *
    * @param string $subdomain subdomain
    * @param string $login     login name
    * @param string $key       key
    * @return void
    */
	function __construct($subdomain,$login,$key,$adapter='curl')
	{
	
		if($key=="efd-api-key"){
	    	$subdomain = "";
	    	$login     = "";
	    	$key       = "";
    	}
    	$this->_subdomain = $subdomain;
    	$this->_login     = $login;
    	$this->_key       = $key;

    	$this->_config = array(
    	     "client"  => "jsonrpc"
    	    ,"jsonrpc" => array("endpoint"=>"https://[subdomain].efactuurdirect.nl/api20/json/")
    	    ,"xmlrpc"  => array("endpoint"=>"https://[subdomain].efactuurdirect.nl/api20/")
    	);		
		$this->_apiUrl    = str_replace("[subdomain]",$this->_subdomain,$this->_config[$this->_config['client']]['endpoint'],$this->_apiUrl);
    	if($this->_config['client']=='xmlrpc'){
    	    include_once('Zend/XmlRpc/Client.php');
    	    $this->client = new Zend_XmlRpc_Client($this->_apiUrl);
    	}
    	if($this->_config['client']=='jsonrpc'){
    	    include_once('Zend/Http/Client.php');
    	    $this->client = new Zend_Http_Client($this->_apiUrl);
    	}
    	$userAgent = array(
    	    "useragent"=>"EfdApiToolkitGateway/".$this->_apiVer
    	);
        if(isset($this->_config['adapter_config'])) {
            $this->setClientConfig(array_merge($userAgent,$this->_config['adapter_config']));
        }if(strtolower($adapter)=='curl'){
			$this->setClientConfig(array_merge($userAgent,array('adapter' => 'Zend_Http_Client_Adapter_Curl')));	
		}else{
            $this->setClientConfig(array_merge($userAgent,array('adapter' => 'Zend_Http_Client_Adapter_Socket')));
        }
	}

	function setClientConfig($config = array())
	{
        if($this->_config['client']=='xmlrpc'){
            $this->client->getHttpClient()->setConfig($config);
        }
		if($this->_config['client']=='jsonrpc'){
		    $this->client->setConfig($config);
		}
	}

	protected function _doRequestXml($method,$params=array())
    {
        $paramsArray = array(
			'data' => $params,
			'auth' => array(
				'login' => $this->_login,
				'key'   => $this->_key
			)
		);
		if(is_array($params)&&count($params)==0){
		    unset($paramsArray['data']);
		}
	    try {
			$result = $this->client->call($method,$paramsArray);
			return $result;
		} catch (Zend_XmlRpc_Client_FaultException $e) {
		    throw new Exception($e);
		} catch (Zend_XmlRpc_Client_HttpException $e) {
		    throw new Exception($e);
		}
    }

    protected function _doRequestJson($method,$params=array())
    {
		$this->client->setMethod(Zend_Http_Client::POST);
        $paramsArray = array(
	        'id'     => 'efd',
	        'method' => $method,
	        'params' => array(
	            'data' => $params,
				'auth' => array(
					'login' => $this->_login,
					'key'   => $this->_key
				)
	        ),
		);
		if(is_array($params)&&count($params)==0){
		    unset($paramsArray['data']);
		}
		$this->client->setRawData(json_encode($paramsArray));
		$requestResult = $this->client->request();
		try {
		    if($requestResult->getStatus()!='200'){
			    throw new Exception("Http code: ".$requestResult->getStatus());
			}
			$result = json_decode($requestResult->getBody(),true);
			if(isset($result["error"])&&$result["error"]!=''){
			    throw new Exception('Code:'.$result["error"]['code'].', Message:'.$result["error"]['message']);
			}
			if(isset($result['result'])){
				return $result['result'];
			}else{
				throw new Exception('No correct result: '.$result);
			}
		} catch (Zend_XmlRpc_Client_FaultException $e) {
		    throw new Exception($e);
		} catch (Zend_XmlRpc_Client_HttpException $e) {
		    throw new Exception($e);
		}
    }

	protected function _doRequest($method,$params=array())
	{
		
	    if($this->_config['client']=='xmlrpc'){
            try {
				$responces =  $this->_doRequestXml($method,$params);
				return $responces;
            } catch (Exception $e) {
                throw $e;
            }
	    }elseif($this->_config['client']=='jsonrpc'){
            try {
				$responces =  $this->_doRequestJson($method,$params);
				return $responces;
            } catch (Exception $e) {
                throw $e;
            }
	    }
	}

	function getDebugInfo()
	{
		if($this->client instanceof Zend_XmlRpc_Client){
            $plainResponce = $this->client->getHttpClient()->getLastResponse()->getBody();
            $plainRequest  = $this->client->getLastRequest();
            $response      = $this->client->getLastResponse();
	    }else{
	        $plainResponce = $this->client->getLastResponse();
	        $plainRequest  = $this->client->getLastRequest();
	        $response      = print_r(json_decode($this->client->getLastResponse()->getBody(),true),true);
	    }
	    return array(
	         "plainResponce" => $plainResponce
	        ,"plainRequest"  => $plainRequest
	        ,"response"      => $response
	    );
	}

    /**
    * Debug method
    *
    * @return array
    */
	public function debug($method,$params=array())
	{
		return $this->_doRequest($method,$params);
	}

}