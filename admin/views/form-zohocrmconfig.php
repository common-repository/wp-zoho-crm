<?php
/**
 * WP Zoho CRM plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

	global $wpdb;
	$active_plugin = get_option('WpLeadBuilderProActivatedPlugin');
	$allowed_html = ['div' => ['class' => true, 'id' => true, 'style' => true, ], 
	'a' => ['id' => true, 'href' => true, 'title' => true, 'target' => true, 'class' => true, 'style' => true, 'onclick' => true,], 
	'strong' => [], 
	'i' => ['id' => true, 'onclick' => true, 'style' => true, 'class' => true, 'aria-hidden' => true, 'title' => true ], 
	'p' => ['style' => true, 'name' => true, 'id' => true, ], 
	'img' => ['id' => true, 'style' => true, 'class' => true, 'align' => true, 'src' => true, 'width' => true, 'height' => true, 'border' => true, ], 
	'table' => ['id' => true, 'class' => true, 'style' => true, 'height' => true, 'cellspacing' => true, 'cellpadding' => true, 'border' => true, 'width' => true, 'align' => true, 'background' => true, 'frame' => true, 'rules' => true, ], 
	'tbody' => [], 
	'br' => ['bogus' => true, ], 
	'tr' => ['id' => true, 'class' => true, 'style' => true, ], 
	'th' => ['id' => true, 'class' => true, 'style' => true, ], 
	'hr' => ['id' => true, 'class' => true, 'style' => true,], 
	'h3' => ['style' => true, ], 
	'td' => ['style' => true, 'id' => true, 'align' => true, 'width' => true, 'valign' => true, 'class' => true, 'colspan' => true, ], 
	'span' => ['style' => true, 'class' => true, ], 
	'h1' => ['style' => true, ], 
	'thead' => [], 
	'tfoot' => ['id' => true, 'style' => true, ], 
	'figcaption' => ['id' => true, 'style' => true, ], 
	'h4' => ['id' => true, 'align' => true, 'style' => true, ],
	'h2' => ['id' => true, 'align' => true, 'style' => true, 'class' => true],
	'script' => [],
	'select' => ['id' => true, 'name' => true, 'class' => true, 'data-size' =>true, 'data-live-search' =>true, 'onchange' => true],
	'option' => ['value' => true, 'selected' => true],
	'label' =>['id' => true, 'class' =>true],
	'input' => ['type' => true, 'value' => true, 'id' => true, 'name' => true, 'class' => true, 'onclick' => true],
	'form' => ['method' => true, 'name' => true, 'id' => true, 'action' => true]];
	$check_shortcode = $wpdb->get_results( $wpdb->prepare("select shortcode_name from wp_smackleadbulider_shortcode_manager where crm_type=%s", $active_plugin));
	$check_field_manager = $wpdb->get_results( $wpdb->prepare("select field_name from wp_smackleadbulider_field_manager where crm_type=%s", $active_plugin));
	$count_shortcode=0;
	$count_shortcode = count($check_shortcode);
	
	if( !empty( $check_field_manager)){
		if( $count_shortcode>1 ){
			$shortcode_available = 'yes';
		}else{
			$shortcode_available = 'no';
		}
	}else{
		$shortcode_available = 'yes';
	}
	$content = "<input type='hidden' id='check_shortcode_availability' value='$shortcode_available'>";
	$content .= "<input type='hidden' id='count_shortcode' value='$count_shortcode'>";
	echo wp_kses($content,$allowed_html);
	// echo "<input type='hidden' id='check_shortcode_availability' value='$shortcode_available'>";
	// echo "<input type='hidden' id='count_shortcode' value='$count_shortcode'>";
	
	//END
	
	$config = get_option("wp_{$active_plugin}_settings");

	if( $config == "" )
	{
		$config_data = 'no';
	}
	else
	{
		$config_data = 'yes';
	}
	
	$site_url = site_url();
	$page_scheme = parse_url($site_url,PHP_URL_SCHEME);
	$server_name = sanitize_text_field($_SERVER['SERVER_NAME']);
	$server_url = sanitize_url($_SERVER['REQUEST_URI']);
	$sales = $page_scheme."://".$server_name.$server_url;
	$sales_url = trim( strtok( $sales , '?' ));
	$sales_query_string = sanitize_text_field($_SERVER['QUERY_STRING']);
	$remove_code = remove_query_arg( 'code' , $sales_query_string );
	$sales_callback_url = site_url().'/wp-admin/admin.php?page=lb-crmconfig';

	if(isset( $_REQUEST['code'] ) && (sanitize_text_field($_REQUEST['code']) != '') && !isset($config['id_token']) )
	{
		include_once(SM_LB_ZOHO_DIR."lib/SmackZohoApi.php");
		$code = sanitize_text_field( $_REQUEST['code'] );

		if(empty($config['refresh_token'])){
			$test=new SmackZohoApi();
			$response =$test->ZohoGet_Getaccess( $config , $code);
			$access_token = $response['access_token'];
			$refresh_token = $response['refresh_token'];
			
			if (!isset($access_token) || $access_token == "") {
			
			}
			$_SESSION['access_token'] = $access_token;
			$_SESSION['instance_url'] = $instance_url;
			$config['access_token'] = $access_token;
			$config['refresh_token'] = $refresh_token;
			$config['api_domain'] = $response['api_domain'];
			update_option("wp_{$active_plugin}_settings" , $config );
		}
	}

	$siteurl = site_url(); 
	$help_img = plugins_url('assets/images/help.png',dirname(__FILE__,2));
	$callout_img = plugins_url('assets/images/callout.gif',dirname(__FILE__,2));
	$help="<img src='$help_img'>";
	$call="<img src='$callout_img'>";

	?>
		
		<div class="clearfix"></div>      
			<div class="" style="display:flex;">
				<div class="panel" id="panel" style="width:80%;">
					<div class="panel-body">
					<div class="form-group ">    
				<div class="col-md-8">
				<div class="crm_head">
						<h3>Zoho CRM</h3>
				</div>
				</div>
				<div class="col-md-4" id="crm_select_dropdown">  
					<label id="inneroptions" class="leads-builder-crm"><?php echo esc_html__('Which CRM do you use?', 'wp-leads-builder-any-crm' ); ?></label>
					<?php $ContactFormPluginsObj = new ContactFormPROPlugins();
						echo wp_kses($ContactFormPluginsObj->getPluginActivationHtml(),$allowed_html);
					?>
				</div>
					</div>
						<input type="hidden" id="get_config" value="<?php echo esc_attr($config_data) ?>" >
						<span id="save_config" style="font:14px;width:200px;"></span>

						<script>
							jQuery( document ).ready( function( ) {
								save_zoho_settings('callback', "<?php echo esc_url($sales_callback_url) ;?>");
							});
						</script>
						<input type="hidden" id="revert_old_crm_pro" value="<?php echo esc_attr($active_plugin); ?>">
						<form id="smack-salesforce-settings-form" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
							<?php wp_nonce_field('sm-leads-builder'); ?>
							<input type="hidden" name="smack-salesforce-settings-form" value="smack-salesforce-settings-form" />
							<input type="hidden" id="plug_URL" value="<?php echo esc_url(SM_LB_URL);?>" />
							
							<div class="clearfix"></div>
							<hr> 
							<div class="mt30">              
								<label id="inneroptions" style="margin-left:14px;margin-bottom:12px"class="leads-builder-heading">Configure your Zoho CRM Settings below!</label>
							</div>

							<div class="clearfix"></div>  
							
							<div class="mt20">
							<div class="form-group col-md-12">
								<div class="col-md-2">
									<label id="innertext" class="leads-builder-label"> <?php echo esc_html__('Authorized redirect URI' , 'wp-leads-builder-any-crm' ); ?> </label>
								</div>
								<div class="col-md-8">
									<input type='text' style="border-radius: 7px;border-color:#9b9797" class='smack-vtiger-settings form-control' name='callback'  id='copy_smack_host_access_key' value="<?php echo esc_url($sales_callback_url); ?>"  onmouseover="this.style.borderColor='#1caf9a'" onmouseout="this.style.borderColor='#9b9797'"disabled="disabled"/>
								</div>    
								<!-- 

								<?php echo esc_url($sales_callback_url); ?>
								-->     
								<!-- <div>
									<img src="<?php echo esc_url($siteurl); ?>/wp-content/plugins/wp-leads-builder-any-crm/assets/images/copy.png" id="copy_to_clipboard" value="Copy"  data-clipboard-action="copy" data-clipboard-target="#copy_smack_host_access_key">
								</div>   -->
							</div>
								<div class="form-group col-md-12">
									<div class="col-md-2">
										<label id="innertext" class="leads-builder-label"> <?php echo esc_attr__('Client ID', 'wp-leads-builder-any-crm' ); ?>  </label>
									</div>
									<div class="col-md-3">
										<input type='text'  style="border-radius: 7px;border-color:#9b9797" class='smack-vtiger-settings form-control' name='key' id='smack_host_address' value="<?php echo esc_attr(isset($config['key']) ? $config['key'] : '') ?>" onblur="save_zoho_settings('key', this.value);"onmouseover="this.style.borderColor='#1caf9a'" onmouseout="this.style.borderColor='#9b9797'"/>
										<div style="position:relative;top:-20px;margin-left:197px;">
											<div class="tooltip">
												<?php echo wp_kses($help,$allowed_html); ?> <span class="tooltipPostStatus"><h5>Consumer Key</h5>Get the Consumer Key from your Salesforce account and specify here.
												<a target="_blank" href="https://help.salesforce.com/apex/HTViewSolution?id=000205876&language=en_US">Refer Salesforce help</a></span> 
											</div>
										</div>
									</div>
									<div class="col-md-2">
										<label id="innertext" class="leads-builder-label"> <?php echo esc_html__('Client Secret', 'wp-leads-builder-any-crm' ); ?> </label>
									</div>
									<div class="col-md-3">
										<input type='password'  style="border-radius: 7px;border-color:#9b9797" class='smack-vtiger-settings form-control' name='secret' id='smack_host_username' value="<?php echo esc_attr(isset($config['secret']) ? $config['secret'] : ''); ?>" onblur="save_zoho_settings('secret', this.value);"onmouseover="this.style.borderColor='#1caf9a'" onmouseout="this.style.borderColor='#9b9797'"/>
										<div style="position:relative;top:-20px;margin-left:197px;">
											<div class="tooltip">
											<?php echo wp_kses($help,$allowed_html); ?>
											<span class="tooltipPostStatus" style="width:330px;">
											<h5>Consumer Secret</h5>Get the Consumer Secret from your Salesforce account and specify here. 
											<a target="_blank" href="https://help.salesforce.com/apex/HTViewSolution?id=000205876&language=en_US">Refer Salesforce Help</a></span> 
										</div>
									</div>
								</div>
							</div> 
	
							<div class="clearfix"></div> 
	

							<div class="clearfix"></div> 
							<div class="form-group col-md-12">
								<div class="col-md-2">
									<label id="innertext" class="leads-builder-label"> <?php echo esc_html__('Available Domains' , 'wp-leads-builder-any-crm' ); ?> </label>
								</div>
								<div class="col-md-8">
									<?php $config = get_option("wp_{$active_plugin}_settings"); 
									$config['domain'] = isset($config['domain'])?$config['domain']:'';?>
									<select name = "domainselect" style="border-radius: 7px;border-color:#9b9797" id ="domainselect" class='smack-vtiger-settings form-control' onblur="save_zoho_settings('domain', this.value);"onmouseover="this.style.borderColor='#1caf9a'" onmouseout="this.style.borderColor='#9b9797'">
										<optgroup label='Available Domains'>
											<option value='.com'<?php if($config['domain'] == '.com'): ?> selected=selected<?php endif; ?>> zoho.com </option>	
											<option value='.eu'<?php if($config['domain'] == '.eu'): ?> selected=selected<?php endif; ?>> zoho.eu </option>
											<option value='.au'<?php if($config['domain'] == '.au'): ?> selected=selected<?php endif; ?>> zoho.au </option>				
											<option value='.in'<?php if($config['domain'] == '.in'): ?> selected=selected<?php endif; ?>> zoho.in </option>
										</optgroup>	
									</select>
								</div> 
							</div>

							</div> <!--label hole div mt close --> 

							<div class="clearfix"></div>

							<input type="hidden" id="posted" name="posted" value="<?php echo esc_attr('posted');?>">
							<input type="hidden" id="site_url" name="site_url" value="<?php echo esc_attr($siteurl) ;?>">
							<input type="hidden" id="active_plugin" name="active_plugin" value="<?php echo esc_attr($active_plugin); ?>">
							<input type="hidden" id="leads_fields_tmp" name="leads_fields_tmp" value="smack_wpsalesforcepro_leads_fields-tmp">
							<input type="hidden" id="contact_fields_tmp" name="contact_fields_tmp" value="smack_wpsalesforcepro_contacts_fields-tmp">
								
							<div class="col-md-12">
								<?php if( !isset($config['refresh_token'])) {?>
									<div class="pull-right1">
										<span>
											<a class="call-back-btn-authentication" ><input name="submit" type="button" value="<?php echo esc_attr__('Authenticate' , 'wp-leads-builder-any-crm' ); ?>" class="authenticate" onclick="redirectZohoCRM();"/> </a>
										</span>
									</div>
								<?php } else { ?>
									<div class="form-group col-md-12">
										<div class="col-md-9">
											<span><input type="button"class="save_config_button_zoho" id="Save_crm_config" value="<?php echo esc_attr__('Save CRM Configuration' , 'wp-leads-builder-any-crm' );?>" id="save"   onclick="saveCRMConfiguration(this.id);" />
											</span>
										</div>
									</div>
								<?php } ?>
							</div>
							<!--<div class="mt30">
							<label class="sf-crm-note"><?php echo esc_html__('Note : The included API add-on supports only Enterprise edition, Unlimited edition and Developer edition of sales force.' , 'wp-leads-builder-any-crm'  ); ?></label>
							</div> --> 

						<form>

						<div id="loading-sync" style="display: none; background:url(<?php echo esc_url(plugins_url('assets/images/ajax-loaders.gif',dirname(__FILE__,2)));?>) no-repeat center"><?php echo esc_html__('' , 'wp-leads-builder-any-crm' ); ?></div>
						<div id="loading-image" style="display: none; background:url(<?php echo esc_url(plugins_url('assets/images/ajax-loaders.gif',dirname(__FILE__,2)));?>) no-repeat center"><?php echo esc_html__('' , 'wp-leads-builder-any-crm' ); ?></div>
					</div>
				</div>
				<div class="card" >
					<h2 class="title2" style="font-size:medium;font-weight:bold">WP Leads Builder for CRM PRO*</h2>
					<hr class="divider"/>
					<b style="font-size: small;font-style: italic;color:#1caf9a">* Use your favorite CRM</b>
					<p style="padding-left: 11%;">Works with JoForce CRM, Zoho CRM, Vtiger CRM, Salesforce CRM, Freshsales, Zoho CRM Plus,SugarCRM and SuiteCRM</p>
					<b style="font-size: small;font-style: italic;color:#1caf9a">* Create New Form or Use Existing Form</b>
					<div style="padding-left: 11%;"><p>Integrate the existing Contact Form 7, Gravity Form, Ninja Form & our default forms to build CRM Leads/Contacts</p></div>
					<b style="font-size: small;font-style: italic;color:#1caf9a">* Bring all your WordPress users</b> 
					<div style="padding-left: 11%;"><p>Capture the WordPress users as Leads or Contacts into the CRM</p></div>
					<b style="font-size: small;font-style: italic;color:#1caf9a">* Integrate with WooCommerce</b> 
					<div style="padding-left: 11%;"><p>Capture the failed order customer information as Leads and successful order customer details as Contacts into the CRM</p></div>
					<a class="cus-button-1" href="https://www.smackcoders.com/wp-leads-builder-any-crm-pro.html?utm_source=plugin&utm_campaign=promo_widget&utm_medium=pro_edition" target="blank">Buy NOW!</a>
				</div>
			</div>
				<!-- </div>   -->
			<div class="container" style="width:100%;">	
				<div class="modal fade" id="smack_confirm_modal" style='margin-top:1%;display:none'>
					<div id="overlay"></div>
					<div class="modal-dialog">
						<!-- Modal content-->
						<div class="modal-content" style='width:525px;'>
							<div class="modal-body">
								<h5><b><center class='popup_content'>Switching to another CRM Will make Your Old ShortCodes Disabled</center></b></h5>
								<br/>
								<!-- <div class="delete-butrons" style="float:right"> -->
								<button  type="button" onclick="document.getElementById('smack_confirm_modal').style.display='none'" class="popup_cancel_button"><span>Cancel</span></button>
								<button  type="button" id="confirmnow" onclick='changecrm();' class="popup_confirm_button"><span>Confirm</span></button>
								<!-- </div> -->
							</div>
				</div>
			</div>