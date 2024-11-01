<?php
/**
 * WP Zoho CRM plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

class ZohoCrmSmLBHelper {

	public function __construct() {
		
	}

	public function setEventObj()
	{
		$obj = new mainCrmHelper();
		return $obj;
	}

	public function user_module_mapping_view() {
		include ('views/form-usermodulemapping.php');
	}

	public function mail_sourcing_view() {
		include('views/form-campaign.php');
	}

	public function new_lead_view() {
		global $lb_crm;
		include ('views/form-managefields.php');
	}

	public function new_contact_view() {
		global $lb_crm;
		$module = "Contacts";
		$lb_crm->setModule($module);
		include ('views/form-managefields.php');
	}


	public function show_form_crm_forms() {
		include ('views/form-crmforms.php');
	}

	public function show_form_settings() {
		include ('views/form-settings.php');
	}

	public function show_usersync() {
		include ('views/form-usersync.php');
	}

	public function show_ecom_integ() {
		include ('views/form-ecom-integration.php');
	}

	public function show_vtiger_crm_config() {
		include ('views/form-vtigercrmconfig.php');
	}

	public function show_sugar_crm_config() {
		include ('views/form-sugarcrmconfig.php');
	}

	public function show_suite_crm_config() {
		include ('views/form-suitecrmconfig.php');
	}

	public function show_zoho_crm_config() {
		include ('views/form-zohocrmconfig.php');
	}

	public function show_zohoplus_crm_config() {
		include ('views/form-zohocrmconfig.php');
	}

	public function show_freshsales_crm_config() {
		include ('views/form-freshsalescrmconfig.php');
	}

	public function show_salesforce_crm_config() {
		include('views/form-salesforcecrmconfig.php');
	}

	public function zohoproSettings( $zohoSettArray )
	{
		$result=[];
		$successresult = "Settings Saved";
		$result['success'] = $successresult;
		$result['error'] = 0;
		return $result;
	}

	public function zohoplusproSettings( $zohoSettArray )
	{
		$result=[];
		$successresult = "Settings Saved";
		$result['success'] = $successresult;
		$result['error'] = 0;
		return $result;
	}
}

global $lb_crm;
$lb_crm = new ZohoCrmSmLBHelper();
