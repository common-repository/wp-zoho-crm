<?php
/**
 * WP Zoho CRM plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

include_once(SM_LB_ZOHO_DIR.'lib/SmackZohoApi.php');
class mainCrmHelper{
	public $username;
	public $accesskey;
	public $authtoken;
	public $url;
	public $result_emails;
	public $result_ids;
	public $result_products;
	public $domain;
	public $access_token;
	public $refresh_token;
	public $client_secret;
	public $client_id;
	public $activated_plugin;
	public $instanceurl;
	public $api_domain;
	
	public function __construct()
	{
		$this->activated_plugin = get_option("WpLeadBuilderProActivatedPlugin");
		$zohoconfig=get_option("wp_{$this->activated_plugin}_settings");
		$this->access_token=$zohoconfig['access_token'];
		$this->refresh_token=$zohoconfig['refresh_token'];
		$this->client_id=$zohoconfig['key'];
		$this->client_secret=$zohoconfig['secret'];
		$this->domain = $zohoconfig['domain'];
	}

	public function login()
	{
		$client = new SmackZohoApi();
		return $client;
	}

	public function getAuthenticationKey( $username , $password )
	{
		$client = $this->login();
		$return_array = $client->getAuthenticationToken( $username , $password  );
		return $return_array;
	}

	public function getCrmFields( $module )
	{
		$client=$this->login();
		$config=[];
		$client=new SmackZohoApi();
		$recordInfo = $client->APIMethod( $module , "getFields" , $this->authtoken );
		if($recordInfo['code']=='INVALID_TOKEN' || $recordInfo['code']=='AUTHENTICATION_FAILURE'){
			$get_access_token=$client->refresh_token();
			$exist_config = get_option("wp_{$this->activated_plugin}_settings");
			$config['access_token']=$get_access_token['access_token'];
			$config['api_domain']=$get_access_token['api_domain'];
			$config['key']=$exist_config['key'];
			$config['secret']=$exist_config['secret'];
			$config['callback']=$exist_config['callback'];
			$config['refresh_token']=$exist_config['refresh_token'];
			$config['domain']=$exist_config['domain'];
			update_option("wp_{$this->activated_plugin}_settings",$config);
			$this->getCrmFields('Leads');
		}
		$config_fields = array();
		$AcceptedFields = Array( 'textarea' => 'text' , 'text' => 'string' , 'email' => 'email' , 'boolean' => 'boolean', 'picklist' => 'picklist' , 'varchar' => 'string' , 'website' => 'url' , 'phone' => 'phone' , 'Multi Pick List' => 'multipicklist' , 'radioenum' => 'radioenum', 'currency' => 'currency' , 'dateTime' => 'date' ,  'integer' => 'string' , 'BigInt' => 'string' , 'double' => 'string');
		$count = 0;

		foreach($recordInfo['fields'] as $key => $fields )
		{
			$fields['req']='false';
			if( ($key != '@attributes') )
			{
				if($fields['api_name']=='Company'||$fields['api_name']=='Last_Name')
				{
					$fields['req']='true';
				}

				if( $fields['req'] == 'true' )
				{
					$config_fields['fields'][$count]['wp_mandatory'] = 1;
					$config_fields['fields'][$count]['mandatory'] = 2;
				}
				else
				{
					$config_fields['fields'][$count]['wp_mandatory'] = 0;
				}
				if(($fields['data_type'] == 'picklist') || ($fields['data_type'] == 'Multi Pick List') || ($fields['data_type'] == 'Radio')){
					$optionindex = 0;
					$picklistValues = array();
					foreach($fields['pick_list_values'] as $option)
					{
						$picklistValues[$optionindex]['display_value'] = $option ;
						$picklistValues[$optionindex]['actual_value'] = $option;
						$optionindex++;
					}
					$config_fields['fields'][$count]['type'] = Array ( 'name' => $AcceptedFields[$fields['data_type']] , 'picklistValues' => $picklistValues );
				}
				else
				{
					$config_fields['fields'][$count]['type'] = array("name" => $AcceptedFields[$fields['data_type']]);
				}

				$config_fields['fields'][$count]['name'] = $fields['api_name'];
				$config_fields['fields'][$count]['fieldname'] = $fields['api_name'];
				$config_fields['fields'][$count]['label'] = $fields['field_label'];
				$config_fields['fields'][$count]['display_label'] = $fields['field_label'];
				$config_fields['fields'][$count]['publish'] = 1;
				$config_fields['fields'][$count]['order'] = $count;
				$count++;
			}
			elseif( $fields['@attributes']['isreadonly'] == 'false' && ( $fields['@attributes']['type'] != 'Lookup' ) && ( $fields['@attributes']['type'] != 'OwnerLookup' ) && ( $fields['@attributes']['type'] != 'Lookup' ) )
			{
				if( $fields['@attributes']['req'] == 'true' )
				{
					$config_fields['fields'][$count]['mandatory'] = 2;
					$config_fields['fields'][$count]['wp_mandatory'] = 1;
				}
				else
				{
					$config_fields['fields'][$count]['wp_mandatory'] = 0;
				}

				if(($fields['@attributes']['type'] == 'Pick List') || ($fields['@attributes']['type'] == 'Multi Pick List') || ($fields['@attributes']['type'] == 'Radio')){
					$optionindex = 0;
					$picklistValues = array();
					foreach($fields['val'] as $option)
					{
						$picklistValues[$optionindex]['label'] = $option;
						$picklistValues[$optionindex]['value'] = $option;
						$optionindex++;
					}
					$config_fields['fields'][$count]['type'] = Array ( 'name' => $AcceptedFields[$fields['@attributes']['type']] , 'picklistValues' => $picklistValues );
				}
				else
				{
					$config_fields['fields'][$count]['type'] = array( 'name' => $AcceptedFields[$fields['@attributes']['type']] );
				}
				$config_fields['fields'][$count]['name'] = str_replace(" " , "_", $fields['@attributes']['dv']);
				$config_fields['fields'][$count]['fieldname'] = $fields['@attributes']['dv'];
				$config_fields['fields'][$count]['label'] = $fields['@attributes']['label'];
				$config_fields['fields'][$count]['display_label'] = $fields['@attributes']['label'];
				$config_fields['fields'][$count]['publish'] = 1;
				$config_fields['fields'][$count]['order'] = $count;
				$count++;
			}

		}
		$config_fields['check_duplicate'] = 0;
		$config_fields['isWidget'] = 0;
		$users_list = $this->getUsersList();
		$config_fields['assignedto'] = $users_list['id'][0];
		$config_fields['module'] = $module;
		return $config_fields;
	}

	public function getUsersList()
	{
		$client=new SmackZohoApi();
		$config=[];
		$user_details=[];
		$records = $client->Zoho_Getuser();
		if(!empty($records['code']) && ($records['code']=='INVALID_TOKEN' || $records['code']=='AUTHENTICATION_FAILURE')){
			$get_access_token=$client->refresh_token();
			$exist_config = get_option("wp_{$this->activated_plugin}_settings");
			$config['access_token']=$get_access_token['access_token'];
			$config['api_domain']=$get_access_token['api_domain'];
			$config['key']=$exist_config['key'];
			$config['secret']=$exist_config['secret'];
			$config['callback']=$exist_config['callback'];
			$config['refresh_token']=$exist_config['refresh_token'];
			$config['domain']=$exist_config['domain'];
			update_option("wp_{$this->activated_plugin}_settings",$config);
			$this->getUsersList();
		}
		elseif( isset( $records['users']['@attributes'] ) ) {
			$user_details['user_name'][] = $records['users']['@attributes']['email'];
			$user_details['id'][] = $records['user']['@attributes']['id'];
			$user_details['first_name'][] = $records['user']['@attributes']['email'];
			$user_details['last_name'][] = "";
		}
		else
		{
			foreach($records['users'] as $record) {
				$user_details['user_name'][] = $record['email'];
				$user_details['id'][] = $record['id'];
				$user_details['first_name'][] = $record['email']; 
				$user_details['last_name'][] = ""; 
			}
		}
		return $user_details;
	}

	public function getUsersListHtml( $shortcode = "" )
	{
		$HelperObj = new WPCapture_includes_helper_PRO();
		$activatedplugin = $HelperObj->ActivatedPlugin;
		$formObj = new CaptureData();
		if(isset($shortcode) && ( $shortcode != "" ))
		{
			$config_fields = $formObj->getFormSettings( $shortcode );  // Get form settings 
		}
		$users_list = get_option('crm_users');
		$users_list = $users_list[$activatedplugin];
		$html = "";
		$html = '<select class="selectpicker form-control" name="assignedto" id="assignedto">';
		$content_option = "";
		if(isset($users_list['user_name']))
		$total_count =count($users_list['user_name']);
			for($i = 0; $i < $total_count; $i++)
			{
				$content_option.="<option id='{$users_list['user_name'][$i]}' value='{$users_list['id'][$i]}'";
				if($users_list['id'][$i] == $config_fields->assigned_to)
				{
					$content_option.=" selected";
				}
				$content_option.=">{$users_list['user_name'][$i]}</option>";
			}
		$content_option .= "<option id='owner_rr' value='Round Robin'";
		if( $config_fields->assigned_to == 'Round Robin' )
		{
			$content_option .= "selected";
		}
		$content_option .= "> Round Robin </option>";

		$html .= $content_option;
		$html .= "</select> <span style='padding-left:15px; color:red;' id='assignedto_status'></span>";
		return $html;
	}

	public function getAssignedToList()
	{
		$user_list_array=[];
		$users_list = $this->getUsersList();
		$total_count = count($users_list['user_name']);
		for($i = 0; $i < $total_count ; $i++)
		{
			$user_list_array[$users_list['user_name'][$i]] = $users_list['user_name'][$i];
		}
		return $user_list_array;
	}



	public function assignedToFieldId()
	{
		return "Lead_Owner";
	}

	public function replace_key_function($module_fields, $key1, $key2)
	{
		$keys = array_keys($module_fields);
		$index = array_search($key1, $keys);
		if ($index !== false) {
			$keys[$index] = $key2;
			$module_fields = array_combine($keys, $module_fields);
		}
		return $module_fields;
	}

	public function createRecord( $module , $module_fields )
	{
		$module_field=[];
		$config=[];
		$data=[];
		$zohoapi=new SmackZohoApi();
		$module_field['data']=array($module_fields);
		if(isset($module_fields['SMOWNERID'])){
			if (isset($module_fields['Lead_Owner'])) {
				$module_field['Owner']['id']=$module_fields['Lead_Owner'];
			}
		}
		$record = $zohoapi->Zoho_CreateRecord( $module_field , $this->instanceurl, $this->api_domain , $module );
		if (isset($record['code']) && ($record['code'] == 'INVALID_TOKEN' || $record['code'] == 'AUTHENTICATION_FAILURE')) {
			$get_access_token=$zohoapi->refresh_token();
			$exist_config = get_option("wp_{$this->activated_plugin}_settings");
			$config['access_token']=$get_access_token['access_token'];
			$config['api_domain']=$get_access_token['api_domain'];
			$config['key']=$exist_config['key'];
			$config['secret']=$exist_config['secret'];
			$config['callback']=$exist_config['callback'];
			$config['refresh_token']=$exist_config['refresh_token'];
			$config['domain']=$exist_config['domain'];
			update_option("wp_{$this->activated_plugin}_settings",$config);
			$this->createRecord($module, $module_fields);
		}          
		elseif( $record['data'][0]['code']=='SUCCESS')
		{
			$data['result'] = "success";
			$data['failure'] = 0;
		}
		else
		{
			$data['result'] = "failure";
			$data['failure'] = 1;
			$data['reason'] = "failed adding entry";
		}
		return $data;
	}

	public function checkEmailPresent( $module , $email )
	{
		$result_emails = array();
		$result_ids = array();
		$result_lastnames =[];
		$client = $this->login();
		$email_present = "no";
		$extraparams = "&searchCondition=(Email|=|{$email})"; // Old API Method for search record
		$records = $client->getRecords( $module , "getSearchRecords" , $this->authtoken , "Id , Email" , "" , $extraparams ); // Replaced getSearchRecords by searchRecords
		if(isset( $records['result'][$module]['row']['@attributes'] ))
		{
			$result_lastnames[] = "Last Name";
			$result_emails[] = $email; 
			$result_ids[] = $records['result'][$module]['row']['FL'];
			$email_present = "yes";
		}
		else
		{
			if(!empty($records) && isset($records['result']) && is_array($records['result'][$module]['row']))
			{
				foreach( $records['result'][$module]['row'] as $key => $record )
				{
					$result_lastnames[] = "Last Name";
					$result_emails[] = $email; 
					$result_ids[] = $record['FL'];
					$email_present = "yes";
				}
			}
		}
		$this->result_emails = $result_emails;
		$this->result_ids = $result_ids;
		if($email_present == 'yes')
			return true;
		else
			return false;
	}

	public function duplicateCheckEmailField()
	{
		return "Email";
	}

}
