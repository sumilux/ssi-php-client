<?php


/**     
 * Services_Sumilux_SSI, a PHP5 API for accessing the Sumilux
 * Social Sign-In (SSI) Service.
 *       
 * PHP version 5
 *       
 * LICENSE:
 *       
 * Copyright (c) 2011, Sumilux Technologies, LLC 
 * All rights reserved.
 *       
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions
 * are met:
 *       
 *  * Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the distribution.
 *       
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
 *       
 * @category  Services
 * @package   Services_Sumilux_SSI
 * @author    Steven Li <steven.li@sumilux.com>
 * @copyright 2011 Sumilux Technologies, LLC 
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: $Id$
 * @link      http://pear.sumilux.com/
 * @link      http://www.sumilux.com/ssi/
 *  
 */  



/**
 * This is the main class providing the Sumilux SSI functions. Please note
 * that the terms "applications", "app" and "widget" are all synonyms and are
 * used interchangably in this documentation.
 * 
 * @package Services_Sumilux_SSI
 */

class Services_Sumilux_SSI 
{
	private $appName;
	private $appSecret;
	private static $endpoint = "https://social-sign-in.com/smx";
	
	//---------- Constants ----------
	Const TOKEN_NAME_IN_SESSION = 'smxSessionToken';
	
	//---------- Constructor ---------
	/**
	 * Constructor of the service object, you will need the "widget name"
	 * and "widget secret", as generated from the main SSI site:
	 * http://ssi.sumilux.com/
	 *
	 * @param string $appName the "app name" (also known as the "widget name")
	 * @param string $appSecret the "app secret" (also known as the "widget secret")
	 */
	public function __construct($appName, $appSecret) {
		$this->appName   = $appName;
		$this->appSecret = $appSecret;
	}
	
	//---------- Magical methods ----------
	
	/**
	 * Implement this method, so we can call IMDE like this:
	 * <code>
	 * 	$appsecret = $ssi->getAppSecret(array('gnt'), false);
	 * 	// the same as 
	 * 	// $appsecret = $ssi->callIDME('getAppSecret', array('gnt'), false);
	 * </code>
	 * @param string $method
	 * @param array $params
	 */
	public function __call($method, $params) {
			array_unshift($params, $method);
			return call_user_func_array(array($this, 'callIdme'), $params);
	}
	
	//---------- Private Functions ----------
	
	private function getRpcEndPoint($svcName) {
		return $this->getEndPoint() . '/rpcService/xmlRpcService'; 
	}
	
	// Please keep this method available from outside (public), so that ssi-website can use it.
	/**
	 * @ignore not officially supported yet
	 */
	public function callRpc($serviceName, $method, $params_array)
	{
		$rpcEndPoint = $this->getRpcEndPoint($serviceName);
		$rpcMethod = $serviceName . "." . $method; // convention: "idme.add"
		$request = xmlrpc_encode_request($rpcMethod, $params_array); // RPC request ready, now where to?

		// $url = ConfigManager::getRpcServiceEndPoint($serviceName); // RPC End Point, a URL basically
		$header[] = "Content-type: text/xml";
		$header[] = "Content-length: ".strlen($request);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $rpcEndPoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		// if (isset($_SERVER['HTTPS'])) {
		if ( strtolower(substr($rpcEndPoint,0,5)) == 'https' ) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);     
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
		$data = curl_exec($ch);
		if (curl_errno($ch)) {
		//	print "Curl error: " . curl_error($ch);
			throw new Exception("XMLRPC failed to complete with $rpcEndPoint. "."Curl error: " . curl_error($ch) .'.', 9001);
		} else {
			curl_close($ch);
			// echo "Curl successful <br>" ;
			// var_dump($data);
			$aret = xmlrpc_decode($data);
			if ( is_array($aret) && xmlrpc_is_fault($aret) ) {
				throw new Exception("XMLRPC failed - " . $aret["faultString"], $aret["faultCode"]);
			}
			
			// echo "do_rpc_call() returns: <br>";
			// var_dump($aret);
			
			return $aret;
		}
	}
	
	private function callHelper($token, 
		$serviceName, $objectType, $objectID, $methodName, $methodParam)
	{
		// $sessionToken = PhpLibUtils::fetchSessionToken();
		
		// return RpcManager::callHelper($sessionToken, $serviceName, 
			// $objectType, $objectID, $methodName, $methodParam);
			
		if ( empty($token) )
			throw new Exception("RPC call cannot be made without a valid SessionToken", 9002);

		$params = array($token, $objectType, $objectID, $methodName, $methodParam);
		return $this->callRpc($serviceName, "dispatch", $params); //s pecific XML RPC implementation			
	}
	
	//---------- Getter/Setter Functions ----------
	
	/**
	 * @ignore
	 * This is only useful when hooking up with our config manager, skipping in
	 * for documentation.
	 */
	public static function setEndpoint($e) {
		// throw new Exception("Cannot set EP");
		// echo "<pre>Setting end point: $e</pre>\n";
		self::$endpoint = dirname(dirname($e)); 
	} // param is RPC ep
	
	
	/**
	 *  @ignore
	 * Used when get endpoint for code viewing of app.
	 */
	public function getEndPoint() { return self::$endpoint; }
	

	/**
	 * Set the access token for the SSI instance. All future interactions with the
	 * server will be under the context of this token. This is normally done as soon
	 * as the user signs in.
	 * 
	 * @param $token
	 * @throws Exception if PHP session is not enabled before this method is invoked.
	 */

	public function setToken($token)
	{ 
		$sid = session_id();
		if ( empty($sid) ) {
			throw new Exception("Active PHP session needed to save session token");
		}
		$_SESSION[self::TOKEN_NAME_IN_SESSION] = $token;
	}
	
	private function getToken()
	{
		$sid = session_id();
		if ( empty($sid) ) {
			throw new Exception("Active PHP session needed to fetch session token");
		}
		
		if ( ! array_key_exists(self::TOKEN_NAME_IN_SESSION, $_SESSION) ) // no session entry
			return null;
			
		if ( empty( $_SESSION[self::TOKEN_NAME_IN_SESSION] ) ) // no actual session value
			return null;
			
		return $_SESSION[self::TOKEN_NAME_IN_SESSION];
	}
	
	//---------- Authentication/Log-in Related Functions ----------
	
	/**
	 * Retrieve the "authentication URL". The user's browser should be directed to this
	 * URL, and then it comes back to the "exitURL", it will be through a POST request, and
	 * that the "token" parameter will contain a valid token as the result of the sign-in
	 * process.
	 * 
	 * @param $exitURL The URL to redirect the user after sign-in is completed.
	 */
	public function getAuthURL($exitURL)
	{
		$sig = md5($this->appSecret); // signature
		
		$authURL = $this->getEndPoint()
			. "/owa?exitURL=" . urlencode($exitURL) 
			. "&sig={$sig}&appName={$this->appName}";
		return $authURL;
	}
	
	/**
	 * @ignore
	 * This should be replaced by a call to BZFE soon.
	 */
	public function getAuthID()
	{
		$token = $this->getToken();
		return $this->callRpc("idme", "getAuthID", array($token));
	}
	
	/**
	 * Retrieve all the attributes of the current user
	 * 
	 * @return JSON all attributes in a JSON object
	 */
	public function getAttributes()
	{
		$token = $this->getToken();
		$str = $this->callRpc("idme", "getAttributes", array($token));
		return json_decode($str);
	}
	
	/**
	 * Test is the current user is signed in.
	 * 
	 * @return Boolean
	 */
	public function isSignedIn()
	{
		$token = $this->getToken();
		return (! empty($token) );
	}
	
	//---------- JQForm Related Functions ------
	/**
	 * @ignore not publishing for now

	 * Retrieve the JQForm definition of a given object, the result is suitable to feed
	 * into the JQForm Yii extension, available separately.
	 * 
	 * @param string $objectClass object type
	 * @param string $oid object ID
	 * @return string JQForm definition in text format
	 */
	public function getJqFormDef($objectClass, $oid) // no object id, no parameter
	{
		$oid = (empty($oid)) ? 'current' : $oid;
		return $this->callHelper($this->getToken(), "idme", 
			$objectClass, $oid, "fetchJQForm", null);
	}
	
	/**
	 * @ignore not officially supported yet
	 * Posting the JQForm data back to the server, and retrieve the response, suitable
	 * for display in the JQForm extension.
	 * 
	 * @param string $objectClass object type
	 * @param string $oid object ID
	 * @return string JQForm posting result in text format
	 */
	public function postJqForm($objectClass, $oid=null)
	{
		// $sessionToken = PhpLibUtils::fetchSessionToken();
		$jo = $this->convertPostToJSON(); 
		$jtext = json_encode($jo);
		$oid = (empty($oid)) ? 'current' : $oid;
		$res = $this->callHelper($this->getToken(), "idme", 
			$objectClass, $oid, "modifyOrAdd", $jtext);
		return $res;
	}
	
	/**
	 * Converts all the parameters in the $_POST array into a JSON object, to facilitate
	 * later invokcation of server methods.
	 * 
	 * @return the resulting JSON object (not JSON string)
	 */
	private function convertPostToJSON() // duplicated from Model class in shared.php
	{
		$ret =  array(); // for JSON converstion sake, convert to object at the end!
		foreach($_POST as $pkey => $pval) {
			$keys = explode('_', $pkey, 5);
			$levels = count($keys);
			if ( $levels == 1 ) { // clear to separate it out
				$ret[$pkey] = $pval;
			}
			else {
			  $aref = &$ret; // pass by ref, seeding the loop
			  for( $i=0; $i<$levels; $i++) { //
				$thisKey = $keys[$i];
				
				if ( $i == ($levels-1) ) { // last one
					$aref[$thisKey] = $pval; // actual value
				}
				else  { // intermediate one
					if ( isset ($aref[$thisKey]) ) { // already set
						// do nothing
					}
					else {
						$aref[$thisKey] = array(); // create an empty array	
					}
					$aref = &$aref[$thisKey]; // since it's already set					
				}
			  }
			}
		}
		return (object) $ret;
	}
	
	//---------- Role Related Functiosn ----------

	/**
	 * @ignore not publishign just yet

	 * Retrieve all of the roles defined for the current organization (defined
	 * as the "default organization" or the current user).
	 * 
	 * @return JSON all roles in a JSON object
	 */
    public function getOrgAllRoles()
    {
    	$jt = $this->callHelper($this->getToken(), 'idme',
            "Org", "current", "getAllRoles", null);

        return json_decode($jt);
    }
    
    /**
     * @ignore not publishign user/role stuff just yet

     * Retrieve all the roles a user has with his/her default organization. This function
     * call can only be successful if two conditions are met: 1. The current user is an
     * ADMINISTRATOR of his/her default organization, and 2. the user in question 
     * (identified by uid) is a member of the organization. Otherwise an exception will
     * be thrown.
     * 
     * @param $uid The ID of the user, for whom roles are to be retrieved.
     * @throws Exception, see conditions in description above.
     */
	public function getUserRoles($uid)
	{
		$token = $this->getToken();
		if ( empty($token) )
			throw new Exception("RPC call cannot be made without a valid SessionToken", 9002);

		$jt = $this->callRpc("idme", "dispatchOrgUser", 
			array($token, "getRoles", $uid, null)); 
		return json_decode($jt);
	}

    /**
	 * @ignore not publishing for now

	 * Add a role for a given user in the user's default organization.
	 * 
	 * @return void
	 */
	public function addUserRole($uid, $role)
	{
		/*
		$jt = $this->callHelper($this->getToken(), 'bzfe',
			"Org", $oid, "getOrgUser", $uid);
		$ouid = json_decode($jt);
		
		$this->callHelper($this->getToken(), 'bzfe',
			"OrgUser", $ouid, "addRole", $role);
		*/
		
		$token = $this->getToken();
		if ( empty($token) )
			throw new Exception("RPC call cannot be made without a valid SessionToken", 9002);

		return $this->callRpc("idme", "dispatchOrgUser", 
			array($token, 'addRole', $uid, $role)); 				
	}
	
	/**
	 * @ignore not publishing user/role stuff yet

	 * Remove a role for a user in the user's default organization.
	 * @param String $uid UserID
	 * @param String $role Role Name
	 * @return void
	 */	
	public function removeUserRole($uid, $role)
	{
		$token = $this->getToken();
		if ( empty($token) )
			throw new Exception("RPC call cannot be made without a valid SessionToken", 9002);

		return $this->callRpc("idme", "dispatchOrgUser", 
			array($token, 'removeRole', $uid, $role)); 
	}
	
	
	//---------- Other Functions ----------

	/**
	 * Retrieve a user's profile. Condition: ???
	 * 
	 * @param $uid
	 */
	public function getUserProfile($uid)
	{
		$jt = $this->callHelper($this->getToken(), 'idme',
			"UserProfile", $uid, "fetchObject", null);
		return json_decode($jt);
	}

	
	/**
	 * @ignore Not publishing org/role stuff just yet

	 * Creating an empty organization for the current user, setting him/her as the Administrator.
	 * 
	 * @return void
	 */
	public function createEmptyOrg()
	{
		$this->callHelper($this->getToken(), 'idme',
			"User", "current", "createEmptyOrg", null);
	}
	
	/**
	 * @ignore Not publishing org/role stuff just yet

	 * Retrieve a list of all the organizations in the system.
	 * 
	 * @return JSON JSON array all organizations
	 */
	public function getAllOrgs()
	{
		$jt = $this->callHelper($this->getToken(), 'idme',
			"Org", null, "fetchAll", null);
		return json_decode($jt);
	}
	
	/**
	 * @ignore not publishing org/role stuff yet

	 * Retrieve the list of all the organizations that matches the name.
	 * 
	 * @param string $orgName
	 * @return JSON JSON array all matched organizations
	 */
	public function getOrgByName($orgName){
		$jt = $this->callHelper($this->getToken(), 'idme',
			"Org", null, "fetchOrgByName", $orgName);
		return json_decode($jt);
		
	}
	
	/**
	 * @ignore not publishign org/user stuff yet

	 * Retrieve the list of all the organizations that the user has been in.
	 * 
	 * @param string $userID
	 * @return JSON JSON array all related organizations
	 */
	public function getOrgsByUserId($userID){
		$jt = $this->callHelper($this->getToken(), 'idme',
			'OrgUser', null, 'fetchOrgListByUserID', $userID);
		return json_decode($jt);
	}
	
	
	/**
	 * Retrieve the list of all the users in the curent user's default organization.
	 * 
	 * @return JSON JSON array of all "OrgUser" objects in the current org (i.e. current user's default org)
	 */
	public function getAllUsers()
	{
		$jt = $this->callHelper($this->getToken(), 'idme',
			"OrgUser", "current", "fetchAllUsers", null);
		return json_decode($jt);
	}
	
	/**
	 * @ignore 
	 */
	public function getAllOrgUsers()
	{
		$jt = $this->callHelper($this->getToken(), 'idme',
			"OrgUser", "current", "fetchAllOrgUsers", null);
		return json_decode($jt);
	}
	

	/**
	 * @ignore
	 * Seems like bad function below, anyone still using it?
	 */
    public function getAllOrgUsersById()
    {
    	throw new Exception("Obsolete method??"); // this seems like a weird method.
    	
        $jt = $this->callHelper($this->getToken(), 'idme',
            "OrgUser", "current", "fetchObject", null);

        return json_decode($jt);
    }


    /**
     * @ignore Not publishing yet

     * Retrieve the JQForm representation of the current user's profile, suitable to support
     * the JQForm UI component.
     *
     * @return JSON JSON text representing the form definition.
     */
    public function getUserProfileFormDefinition()
    {
    	return $this->callHelper($this->getToken(), 'idme',
			"UserProfile", "current", "fetchJQForm", null);
	}
	

	
	/**
	 * @ignore not publishing org/role stuff yet
	 * Retrieve the "Org" object for an ORG with a certain ID.
	 * 
	 * @param $oid the ID of the Org.
	 * @return Object The "Org" object for the organization with ID
	 */
	public function getOrgFormDefinition($oid)
	{
		return $this->callHelper($this->getToken(), 'idme',
			"Org", $oid, "fetchJQForm", null);
	}


    /**
     * @ignore not publishing org/role stuff yet

     * Retrieve the JQForm definition for an ORG with a certain ID.
     *
     * @param $oid the ID of the Org.
     * @return JSON JSON text representing the form definition.
     */

  public function getOrg($oid){
        $jt = $this->callHelper($this->getToken(), 'idme',
            "Org", $oid, "fetchObject", null);
        return json_decode($jt);
    }
	

    /**
     * Get user's avatar information
     * @param string $uid   the user ID, 'current' for current signed user
     * @param boolean $returnBinary		if true, return binary data rather than base64 encoded data 
     * @return mixed		if no image data, return null; else return base64-encoded string or binary data
     */
  public function getUserAvatar($uid, $returnBinary=false){
  	
		$userProfile = $this->getUserProfile($uid);
		
		$data = $userProfile->userImage->image;
		if (empty($data)) {
			return null;
		}
		
		if ($returnBinary) {
			$decoded = ""; 
 			for ($i=0, $j = ceil(strlen($data)/256); $i<$j; $i++) {
    		$decoded = $decoded . base64_decode(substr($data, $i*256, 256));
 			}
 			return $decoded;
		} else {
			return $data;
		}
		
    }
    
    /**
     * Refresh the friend list
     */
 public function refreshFriends(){
 		$this->callIdme('refreshFriends', array($this->appName));
 }

    /**
     * Get user friend list. Only supports Facebook and Twitter
     * @param boolean $refresh
     */
	public function getUserFriendList($refresh=false){
		if ($refresh) {
			$this->refreshFriends();	// equals to '$this->callIdme('refreshFriends', array('widgetName'));
		}
		return json_decode($this->callIdme('getUserFriendList'));
	}
    
    
	/**
	 * @ignore Not publishign org/role stuff yet

	 * Set the default organization for the current user
	 * @param String $orgID Organization ID
	 * @return void
	 */
	public function setDefaultOrg($orgID)
	{
		$userID = "current"; // special value, kind of a hack, for now.
		$this->callHelper($this->getToken(), 'idme',
			"User", $userID, "setDefaultOrg", $orgID);		
	}
	
	
	
	
	/**
	 * @ignore not officially supported yet

	 * This method allows idme service to be called outside.
	 * 
	 * @ignore Only used by ssi-website
	 * @param string $method
	 * @param array $param_array		
	 * @param boolean $withToken		if set to true, the param array will be unshifted with token
	 * @throws Exception
	 */
	public function callIdme($method, $param_array=array(), $withToken=true) {
		if (!is_string($method)) {
			throw new Exception(__METHOD__.' expects Parameter 1 to be string, '.gettype($method).' given.');
		}
		if (!is_array($param_array)) {
			throw new Exception(__METHOD__.' expects Parameter 2 to be array, '.gettype($param_array).' given.');
		}
		try{
			if ($withToken) {
				$token = $this->getToken();
				if (empty($token)) {
					throw new Exception("RPC call cannot be made without a valid SessionToken", 97);
				}
				array_unshift($param_array, $token);
			}
			return $this->callRpc('idme', $method, $param_array);
		}catch(Exception $e){
			Yii::getLogger()->log('Method = '.$method.PHP_EOL.'Parameters = '.print_r($param_array, true), 'error', 'idme.method');
			throw $e;
		}
	}
	
	/**
	 * This method generates the HTML code snippets for the sign-in widgets,
	 * to be placed on web pages.
	 * 
	 * @param mixed $widgetName Name of the widget, or the app model
	 * @param string $widgetStyle The style of the widget code snippet to be generated,
	 * valid choices include 'big-icon', 'small-icon', 'text',
	 * @param string $linkText specifies the text of the link if the style is "text"
	 * @param string $siteURL the URL of the web site for the user to be redirected
	 * back to, default is null.
	 * @throws Exception when $widgetStyle parameter is invalid
	 */
	public function getCode($widgetName, $widgetStyle, $linkText=null, $siteURL=null) {
		if (is_string($widgetName)) {
			$app = json_decode($this->callIdme('getAppDetail', array($widgetName)));
		} else if (is_object($widgetName)) {
			$app = $widgetName;
			$widgetName = $app->appName;
		} else {
			throw new BadMethodCallException(__METHOD__.' expect Parameter 1 to be string or object, '.gettype($widgetName).' given.', 93);
		}
		$appKey = $app->appKey;
		$appSecret = $app->appSecret;
		
		return $this->generateCode($widgetName, $appKey, $appSecret, $widgetStyle, $linkText, $siteURL);
		
	}
	
	
	/**
	 * This method generates the HTML code snippets for the sign-in widgets in static context.
	 * 
	 * @param string $widgetName Name of the widget
	 * @param string $appKey appKey of the widget
	 * @param string $appSecret appSecret of the widget
	 * @param string $widgetStyle The style of the widget code snippet to be generated,
	 * valid choices include 'big-icon', 'small-icon', 'text',
	 * @param string $linkText specifies the text of the link if the style is "text"
	 * @return array array('html-head-code' => '', 'html-body-code' => '')
	 * @throws Exception when $widgetStyle parameter is invalid
	 * @version v0.5-1
	 */
	public static function generateCode($widgetName, $appKey, $appSecret, $widgetStyle, $linkText=null, $siteURL=null){
		
		$version = 'v0.5-1';
		
		if (empty($linkText)) {
			$linkText = 'Sign In';
		}
		
		if (empty($siteURL)) {
			$siteURL = '__TOKEN_URL__';
			$comment = ' // replace __TOKEN_URL__ with your own callback URL';
		} else {
			$comment = '';
		}
		
		$owaSrc = self::$endpoint . '/owa';
		$authSrc = "{$owaSrc}/js/app/{$appKey}.js";
		
		// the source files path
		if (stripos($owaSrc, 'social-sign-in') !== false) {
			$sourcePath = 'http://ssi.sumilux.com/ssi/download';
		} else if (stripos($owaSrc, 'demo') !== false) {
			$sourcePath = 'http://demo.sumilux.com/ssi/download';
		} else {
			if (!empty($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], '172.25') !== false) {
				$sourcePath = 'http://172.25.1.96/~yaowenh/ssi/ssi-website/download';
			} else {
				$sourcePath = 'http://demo.sumilux.com/ssi/download';
			}
		}
		
		$sig = md5($appSecret);
		$cssSrc = $sourcePath.'/ssi.css';
				
		if ( $widgetStyle == 'big-icon') {
			$funcJs = $sourcePath.'/popup.js';
			$head_code = <<<POP_UP_HEADER
<link type="text/css" rel="stylesheet" href="{$cssSrc}">
<script type="text/javascript">
window.SSI={
    tokenUrl: "{$siteURL}",{$comment}
    appName:"{$widgetName}",
    sig:"{$sig}",
    owaUrl:"{$owaSrc}",
    v:"{$version}"
};
(function(){
    var e=document.createElement("script");
    e.type="text/javascript"; e.src="{$authSrc}";
    var f=document.createElement("script");
    f.type="text/javascript"; f.src="{$funcJs}";
    var h=document.getElementsByTagName("script")[0];
    h.parentNode.insertBefore(e, h); h.parentNode.insertBefore(f, h);
})();
</script>
POP_UP_HEADER;

			$body_code = <<<POP_UP_BODY
<div style="padding:20px">
    <div style="text-align:center";>
        <a href="#none" onclick="SSI.popLoginPage();">{$linkText}</a>
    </div>
</div>
POP_UP_BODY;
		} else if ( $widgetStyle == 'small-icon') {
			$funcJs = $sourcePath.'/emb.js';
			$head_code = <<<EMBEDDED_HEADER
<link type="text/css" rel="stylesheet" href="{$cssSrc}">
<script type="text/javascript">
window.SSI={
    tokenUrl: "{$siteURL}",{$comment}
    appName:"{$widgetName}",
    sig:"{$sig}",
    owaUrl:"{$owaSrc}",
    v:"{$version}"
};
(function(){
    var e=document.createElement("script");
    e.type="text/javascript"; e.src="{$authSrc}";
    var f=document.createElement("script");
    f.type="text/javascript"; f.src="{$funcJs}";
    var h=document.getElementsByTagName("script")[0];
    h.parentNode.insertBefore(e, h); h.parentNode.insertBefore(f, h);
})();
</script>
EMBEDDED_HEADER;
			$body_code = <<<EMBEDDED_BODY
<div id="smx_ssi">
    <div id="smx_lastsign"></div>
    <div id="smx_linklist"></div>
</div>
EMBEDDED_BODY;
		} else if ( $widgetStyle == 'text') {
			$funcJs = $sourcePath.'/link.js';
			$head_code = <<<LINK_HEAD
<script type="text/javascript">
window.SSI={
    tokenUrl: "{$siteURL}",{$comment}
    appName:"{$widgetName}",
    sig:"{$sig}",
    owaUrl:"{$owaSrc}",
    v:"{$version}"
};
(function(){
    var e=document.createElement("script");
    e.type="text/javascript"; e.src="{$authSrc}";
    var f=document.createElement("script");
    f.type="text/javascript"; f.src="{$funcJs}";
    var h=document.getElementsByTagName("script")[0];
    h.parentNode.insertBefore(e, h); h.parentNode.insertBefore(f, h);
})();
</script>
LINK_HEAD;
			$body_code = <<<LINK_BODY
<div id="smx_ssi">
    <a href="#" onclick="SSI.doLogin();">{$linkText}</a>
</div>
LINK_BODY;
		} else {
			throw new BadMethodCallException("Unknown widget style: " + $widgetStyle);
		}
		
		return array('html-head-code' => $head_code, 'html-body-code' => $body_code);
	}
	
	
}

?>
