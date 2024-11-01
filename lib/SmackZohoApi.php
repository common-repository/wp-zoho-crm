<?php
/**
 * WP Zoho CRM plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

if( !class_exists( "SmackZohoApi" ) )
{
	class SmackZohoApi{
		public $zohocrmurl;
		public $access_token;
		public $refresh_token;
		public $callback;
		public $client_id;
		public $client_secret;
		public $domain;

		/**
		 *ZohoApi Constructor
		 **/
		public function __construct()
		{
			$activated_plugin = get_option("WpLeadBuilderProActivatedPlugin");
			$zohoconfig=get_option("wp_{$activated_plugin}_settings");
			$this->access_token=$zohoconfig['access_token'];
			$this->refresh_token=$zohoconfig['refresh_token'];
			$this->callback=$zohoconfig['callback'];
			$this->client_id=$zohoconfig['key'];
			$this->client_secret=$zohoconfig['secret'];
			$this->domain = $zohoconfig['domain'];
		}

		/**
		 *Code to get crm fields
		 **/
		public function APIMethod($module, $methodname, $authkey , $param="", $recordId = "")
		{
			$url = "https://www.zohoapis".$this->domain."/crm/v5/settings/fields?module=Leads";
			$args = array(
					'headers' => array(
						'Authorization' => 'Zoho-oauthtoken '.$this->access_token
						)
				     );
			$response = wp_remote_retrieve_body( wp_remote_get($url, $args ) );
			$body = json_decode($response, true);
			return $body;
		}

		/**
		 *Code for create record in zoho
		 **/
		public function Zoho_CreateRecord( $data_array, $instance_url, $access_token , $module = "Lead" ) {
			$apiUrl = "https://www.zohoapis".$this->domain."/crm/v5/$module";
			$fields = json_encode($data_array);
			$response = wp_remote_post( $apiUrl, array(
						'method' => 'POST',
						'sslverify' => false,
						'headers' => array(
							'Content-Type: application/json',
							'Content-Length: ' . strlen($fields),
							'Authorization' => 'Zoho-oauthtoken '.$this->access_token
							),
						'body' => $fields
						)
					);	
			$result = wp_remote_retrieve_body($response);
			$result_array = json_decode($result,TRUE);
			return $result_array;
		}

		/**
		 *Code to get access token
		 **/
		public function ZohoGet_Getaccess( $config , $code ) {
			$token_url = "https://accounts.zoho".$this->domain."/oauth/v2/token?";
			$params = "code=" .$code
				. "&redirect_uri=" . $this->callback
				. "&client_id=" . $this->client_id
				. "&client_secret=" . $this->client_secret
				. "&grant_type=authorization_code";
			$args = array(
					'body' => $params
				     );
			$response =  wp_remote_post($token_url, $args ) ; 
			$result = wp_remote_retrieve_body($response);
			$http_code = wp_remote_retrieve_response_code($response );
			if ( $http_code != 200 ) {
				die("Error: call to token URL $token_url failed with status " );
			}
			$response = json_decode($result, true);
			return $response;
		}

		/**
		 *Code for insert record
		 **/
		public function insertRecord( $modulename, $methodname, $authkey , $xmlData="" , $extraParams = "" )
		{
			$uri = $this->zohocrmurl . $modulename . "/".$methodname."";
			/* Append your parameters here */
			$postContent = "scope=crmapi";
			$postContent .= "&authtoken={$authkey}";//Give your authtoken
			if($extraParams != "" && !is_array($extraParams) )
			{
				$postContent .= $extraParams;
			}
			$postContent .= "&xmlData={$xmlData}";
			$postContent .= "&wfTrigger=true";
			$args = array(
					'body' => $postContent
				     );
			$response =  wp_remote_post($uri, $args ) ;
			$result = wp_remote_retrieve_body($response);
			$xml = simplexml_load_string($result);
			$json = json_encode($xml);
			$result_array = json_decode($json,TRUE);
			//Attachment
			if($extraParams && is_array($extraParams)){
				foreach($extraParams as $field => $path){
					$this->insertattachment($result_array,$authkey,$path,$modulename);//Feb03 fix
				}
			}
			//Attachment
			return $result_array;
		}

		//Attachment
		public function insertattachment( $result_array,$authkey,$path,$modulename){
			$recordId = $result_array['result']['recorddetail']['FL'][0];
			$uri = $this->zohocrmurl . $modulename . "/uploadFile?authtoken=".$authkey."&scope=crmapi";
			$path = '@'.$path;

			$post=array("id"=>$recordId,"content"=>$path);
			$args = array(
					'body' => $post
				     );
			$response =  wp_remote_post($uri, $args ) ;
			$result = wp_remote_retrieve_body($response);

		} //Attachment


		public function getRecords( $modulename, $methodname, $authkey , $selectColumns ="" , $xmlData="" , $extraParams = "" )
		{
			$uri = $this->zohocrmurl . $modulename . "/".$methodname."";
			/* Append your parameters here */
			$postContent = "scope=crmapi";
			$postContent .= "&authtoken={$authkey}";//Give your authtoken
			if($selectColumns == "")
			{
				$postContent .= "&selectColumns=All";
			}
			else
			{
				$postContent .= "&selectColumns={$modulename}( {$selectColumns} )";
			}

			if($extraParams != "")
			{
				$postContent .= $extraParams;
			}
			$postContent .= "&xmlData={$xmlData}";
			$args = array(
					'body' => $postContent
				     );
			$response =  wp_remote_post($uri, $args ) ;
			$result = wp_remote_retrieve_body($response);
			$xml = simplexml_load_string($result);
			$json = json_encode($xml);
			$result_array = json_decode($json,TRUE);
			return $result_array;
		}

		/**
		 *Code to get access token based on refresh_token
		 **/
		public function refresh_token() {
			$token_url = "https://accounts.zoho".$this->domain."/oauth/v5/token?";
			$params = "&refresh_token=" . $this->refresh_token
				. "&client_id=" . $this->client_id
				. "&client_secret=" . $this->client_secret
				. "&grant_type=refresh_token";
			$args = array(
					'body' => $params
				     );
			$response =  wp_remote_post($token_url, $args ) ; 
			$result = wp_remote_retrieve_body($response);
			$http_code = wp_remote_retrieve_response_code($response );
			if ( $http_code != 200 ) {
				die("Error: call to token URL $token_url failed with status ");
			}

			$response = json_decode($result, true);
			return $response;
		}

		public function getAccountId($authkey)
		{
			$Account_uri = "https://crm.zoho".$this->domain."/crm/private/xml/Accounts/getRecords";
			$Account_postContent = "scope=crmapi";
			$Account_postContent .= "&authtoken={$authkey}";//Give your authtoken
			$Account_postContent .= "&selectColumns=Accounts(ACCOUNTID)";

			$args = array(
					'body' => $Account_postContent
				     );
			$response =  wp_remote_post($Account_uri, $args ) ;
			$result = wp_remote_retrieve_body($response);

			$xml = simplexml_load_string($result);
			$json = json_encode($xml);
			$result_array = json_decode($json,TRUE);

			$ACCOUNT_ID = $result_array['result']['Accounts']['row'][0]['FL'];
			return $ACCOUNT_ID;
		}

		public function Zoho_Getuser()
		{
			$url = "https://www.zohoapis".$this->domain."/crm/v5/users?type=AllUsers";
			$args = array(
					'headers' => array(
						'Authorization' => 'Zoho-oauthtoken '.$this->access_token
						)
				     );
			$response = wp_remote_retrieve_body( wp_remote_get($url, $args ) );
			$body = json_decode($response, true);
			return $body;
		}
		public function getModules($TFA_authtoken)
		{
			$uri = "https://crm.zoho".$this->domain."/crm/private/xml/Info/getModules?"; // Check Auth token present in Zoho //ONLY FOR TFA CHECK
			$postContent = "scope=crmapi";
			$postContent .= "&authtoken={$TFA_authtoken}";
			$args = array(
					'body' => $postContent
				     );
			$response =  wp_remote_post($uri, $args ) ;
			$result = wp_remote_retrieve_body($response);
			$xml = simplexml_load_string($result);
			$json = json_encode($xml);
			$result_array = json_decode($json,TRUE);

			return $result_array;

		}

		public function getConvertLeadOwner($modulename , $authkey , $record_id )
		{
			$zohourl = "https://crm.zoho".$this->domain."/crm/private/xml/";
			// $methodname = 'getRecords';
			$module_slug = rtrim( $modulename , 's' );
			// $uri = $zohourl . $modulename . "/".$methodname."";
			$postContent = "scope=crmapi";
			$postContent .= "&authtoken={$authkey}";//Give your authtoken
			$postContent .= "&id={$record_id}&selectColumns={$modulename}({$module_slug} Owner)";

			$args = array(
					'body' => $postContent
				     );
			$response =  wp_remote_post($zohourl, $args ) ;
			$result = wp_remote_retrieve_body($response);
			$xml = simplexml_load_string($result);
			$json = json_encode($xml);
			$result_array = json_decode($json,TRUE);
			$Lead_owner = $result_array['result'][$modulename]['row']['FL'][1];
			return $Lead_owner;
		}

		public function getAuthenticationToken( $username , $password  )
		{
			$username = urlencode( $username );
			$password = urlencode( $password );
			$return_array=[];
			$param = "SCOPE=ZohoCRM/crmapi&EMAIL_ID=".$username."&PASSWORD=".$password;
			$url = "https://accounts.zoho".$this->domain."/apiauthtoken/nb/create";
			$args = array(
					'body' => $param
				     );
			$response =  wp_remote_post($url, $args ) ;
			$result = wp_remote_retrieve_body($response);
			$anArray = explode("\n",$result);
			$authToken = explode("=",$anArray['2']);
			$cmp = strcmp($authToken['0'],"AUTHTOKEN");
			if ($cmp == 0)
			{
				$return_array['authToken'] = $authToken['1'];
			}
			$return_result = explode("=" , $anArray['3'] );
			$cmp1 = strcmp($return_result['0'],"RESULT");
			if($cmp1 == 0)
			{
				$return_array['result'] = $return_result['1'];
			}
			if($return_result[1] == 'FALSE'){
				$return_cause = explode("=",$anArray[2]);
				$cmp2 = strcmp($return_cause[0],'CAUSE');
				if($cmp2 == 0)
					$return_array['cause'] = $return_cause[1];
			}

			return $return_array;
		}
	}
}