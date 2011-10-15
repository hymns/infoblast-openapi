<?php
/**
 * openapi.class.php
 *
 * Library for infoblast open api (application programming interface) sms gateway
 *
 * @package		openapi
 * @author		Muhammad Hamizi Jaminan, hymns [at] time [dot] net [dot] my
 * @copyright		Copyright (c) 2008 - 2011, Green Apple Software.
 * @license		LGPL, see included license file
 * @link		http://github.com/hymns/infoblast-openapi
 * @since		Version 11.10 RC1
 */

/**
 //----------------------------------------
 // Example usage #1
 //----------------------------------------

 // openapi auth configuration
 $config['username'] = 'infoblast_api_username';
 $config['password'] = 'infoblast_api_password';

 // include openapi library
 include 'openapi.class.php';

 // call openapi class
 $openapi = new openapi();
 $openapi->initialize($config);

 // get sms list - parameters 1 & 2 is optional. [default]
 // parameter 1 sms status to read : [new] / old / all
 // parameter 2 is for delete sms after fetch :  true / [false]
 $text_messages = $openapi->get_sms('new', false);

 // print sms array
 print_r($text_messages);

 //----------------------------------------
 // Example usage #2
 //----------------------------------------

 // openapi auth configuration
 $config['username'] = 'infoblast_api_username';
 $config['password'] = 'infoblast_api_password';

 // include openapi library
 include 'openapi.class.php';

 // call openapi class
 $openapi = new openapi();
 $openapi->initialize($config);

 // prepair data
 $data['msgtype']   = 'text';
 $data['to']        = '0123456789'; //separate by comma for multiple reciepient with same text message
 $data['message']   = 'this is short messaging service demo from phphyppo openapi application library';

 // send sms to open api
 $response = $openapi->send_sms($data);

 // print response
 print_r($response);

 //----------------------------------------
 // Example usage #3
 //----------------------------------------

 // openapi auth configuration
 $config['username'] = 'infoblast_api_username';
 $config['password'] = 'infoblast_api_password';

 // include openapi library
 include 'openapi.class.php';

 // call openapi class
 $openapi = new openapi();
 $openapi->initialize($config);

 // prepair data
 $data['msgid']  = '12345678901234567890';
 
 // get send status to open api
 $status = $openapi->send_status($data);

 // print response
 print_r($status);
 */

class OpenAPI
{
	/**
	 * $username
	 *
	 * vars for openapi username
	 *
	 * @access private
	 */
	private $username;

	/**
	 * $password
	 *
	 * vars for openapi password
	 *
	 * @access private
	 */
	private $password;

	/**
	 * $login_session
	 *
	 * vars for current session id
	 *
	 * @access public
	 */
	private $login_session = null;

	/**
	 * $openapi_url_*
	 *
	 * variable for open api url path
	 *
	 * @access private
	 */
	private $openapi_url_login 	= 'http://www.infoblast.com.my/openapi/login.php';
	private $openapi_url_logout	= 'http://www.infoblast.com.my/openapi/logout.php';
	private $openapi_url_spool 	= 'http://www.infoblast.com.my/openapi/getmsglist.php';
	private $openapi_url_view 	= 'http://www.infoblast.com.my/openapi/getmsgdetail.php';
	private $openapi_url_delete	= 'http://www.infoblast.com.my/openapi/delmsg.php';
	private $openapi_url_send 	= 'http://www.infoblast.com.my/openapi/sendmsg.php';
	private $openapi_url_status 	= 'http://www.infoblast.com.my/openapi/getsendstatus.php';

 	/**
	 * class constructor
	 *
	 * @access	public
	 */
	function __construct()
	{

	}

	/**
	 * initialize
	 *
	 * this function use to load openapi username & password or other
     * configuration vars
	 *
	 * @access public
	 * @param array $config
	 */
	public function initialize($config)
	{
		// extract configuration array
		foreach($config as $key => $val)
			$this->$key = $val;

		// auth user session
		$this->login_session = $this->_auth();
	}

	/**
	 * get_sms
	 *
	 * get sms list from openapi server
	 *
	 * @access public
	 * @param string $status (optional, default: new)
	 * @param bool $delete (optional, default: false)
	 * @return array
	 */
	public function get_sms($status = 'new', $delete = false)
	{
		// no session - halt
		if ($this->login_session === null)
			return null;

		// openapi session & sms data
		$data['sessionid'] = $this->login_session;
		$data['status'] = $status;

		// fetch sms list from openapi server
		$content = $this->_fetch_process($this->openapi_url_spool, $data);

		// reset status vars
		unset($data['status']);

		// build sms listing
		$sms_list = $this->_build_list($content);

		// count sms listing
		if (sizeof($sms_list) > 0)
		{
			// fetch from sms list one by one - arghhh
			foreach($sms_list as $num)
			{
				// prepair data
				$data['uid'] = $num;

				// fetch sms detail from openapi server
				$content = $this->_fetch_process($this->openapi_url_view, $data);

				// update using dom
				$dom = new DomDocument('1.0', 'utf-8');
				$dom->loadXML($content);
				$object = simplexml_import_dom($dom->documentElement);

				// make sure no broken xml data
				if (is_object($object))
				{
					// generate complete text message list
					$record[$num]['uid'] = (int) $num;
					$record[$num]['datetime'] = empty($object->msginfo->datetime) ? time() : (int) $object->msginfo->datetime;
					$record[$num]['from'] = (string) $object->msginfo->from;
					$record[$num]['to'] = (string) $object->msginfo->to;
					$record[$num]['subject'] = (string) $object->msginfo->subject;
					$record[$num]['msgtype'] = (string) $object->msginfo->msgtype;
					$record[$num]['message'] = (string) $object->msginfo->message;

					// delete after fetch?
					if ($delete === true)
						$this->_fetch_process($this->openapi_url_delete, $data);
				}
			}
		}

		// logging out after fetching
		unset($data['uid']);
		$this->_fetch_process($this->openapi_url_logout, $data);

		// return record set
		return $record;
	}

	/**
	 * send_sms
	 *
	 * send sms list to open api gateway
	 *
	 * @access public
	 * @param array $data
	 * @return string
	 */
	public function send_sms($data)
	{
		// no session - halt
		if ($this->login_session === null)
			return null;

		// merge session id with text message data
		$tmp = array('sessionid' => $this->login_session);
		$data = array_merge($tmp, $data);

		// fetch sms send status from openapi server
		$content = $this->_fetch_process($this->openapi_url_send, $data);

		// logging out after fetching
		$this->_fetch_process($this->openapi_url_logout, $tmp);

		// return sending status
		return $this->_status($content);
	}


	/**
	 * send_status
	 *
	 * get sms send status to open api gateway
	 *
	 * @access public
	 * @param array $data
	 * @param bool $fullstatus
	 * @return mixed
	 */
	public function send_status($data, $fullstatus=false)
	{
		// no session - halt
		if ($this->login_session === null)
			return null;

		// merge session id with text message data
		$tmp = array('sessionid' => $this->login_session);
		$data = array_merge($tmp, $data);
		
		// fetch sms send status from openapi server
		$content = $this->_fetch_process($this->openapi_url_status, $data);

		// logging out after fetching
		$this->_fetch_process($this->openapi_url_logout, $tmp);
		
		// convert xml to array
		$object =  @simplexml_load_string($content);
		$response = null;
		
		// verify object
		if (is_object($object))
		{
			// get full status from object
			if ($fullstatus)
			{
				$response['msgid'] = (string) $object->stats->record->msgid;
				$response['datetime'] = (int) $object->stats->record->enddate;				
				$response['from'] = (string) $object->stats->record->aparty;
				$response['to'] = (string) $object->stats->record->bparty;
				$response['status'] = (string) $object->stats->record->status;
			}
			else
			{
				$response = (string) $object->stats->record->status;
			}
		}
		
		// return sent status
		return $response;
	}

	/**
	 * _auth
	 *
	 * authenticate session to openapi
	 *
	 * @access private
	 * @return string
	 */
	private function _auth()
	{
		// prepair auth username & password
		$data['username'] = $this->username;
		$data['password'] = sha1($this->password);

		// fetch auth session data
		$content = $this->_fetch_process($this->openapi_url_login, $data);

		// convert xml data to array object
		$object = @simplexml_load_string($content);

		// return auth data
		return (is_object($object) && isset($object->sessionid)) ? (string) $object->sessionid : null;
	}

	/**
	 * _build_list
	 *
	 * extract xml data for attributes name spaces
	 * and build data as array list
	 *
	 * @access private
	 * @param string $xml
	 * @param string $attr (optional) default : uid
	 * @return array
	 */
	private function _build_list($xml, $attr = 'uid')
	{
		// convert xml data to array object
		$object = @simplexml_load_string($xml);

		// count total object record set
		$total = sizeof($object);

		// we got an record set
		if ($total > 0)
		{
			// loop over record set
			for ($i=0; $i < $total; $i++)
			{
				// extract data from array object
				foreach($object->msginfo[$i]->attributes() as $key => $val)
				{
					// define right attribute
					if ($key == $attr)
						$record[] = (int) $val;
				}
			}

			// return record set
			return $record;
		}

		// no data
		else
			return null;
	}

	/**
	 *  _fetch_process
	 *
	 * Fetching web using fopen function
	 *
	 * @access private
	 * @param string $url
	 * @param array $data (optional)
	 * @param string $optional_header (optional)
	 * @return string
	 */
	private function _fetch_process($url, $data = null, $optional_header = null)
	{
		$param = array(
		                'http' => array(
										'method' => 'POST',
										'header' => "Content-type: application/x-www-form-urlencoded\r\n",
										'content' => http_build_query($data)
										)
		                );

		// assign custom header
		if ($optional_header !== null)
			$param['http']['header'] = $optional_header;

		// create context stream
		$context = stream_context_create($param);
		$handler = fopen($url, 'rb', false, $context);

		// fetch url failed
		if (!$handler)
			throw new Exception('Unable to connect to ' . $url);

		// fetch content data
		$content = stream_get_contents($handler);

		// reading content data failed
		if ($content === false)
			throw new Exception('Unable to read data from ' . $url);

		return $content;
	}

	/**
	 * _status
	 *
	 * get sms send status from output
	 *
	 * @access private
	 * @return string
	 */
	private function _status($xml, $attr = 'status')
	{
		// convert xml data to array object
		$object = @simplexml_load_string($xml);
		$response = null;
		
		// check reponse object
		if (is_object($object))
		{
			// extract data from array object
			foreach($object->attributes() as $key => $val)
			{
				// define right attribute
				if ($key == $attr)
					$status = (string) $val;
			}
			
			$response['status'] =	$status = trim($status);
			$response['messageid'] = (string) $object->messageid;
		}

		// return status
		return $response;
	}
}

/* End of openapi.php */
/* Location:  openapi.php */
?>