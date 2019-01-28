<?php
/**
 * Plugin Name: Daily Email
 * Plugin URI: N/A
 * Description: This plugin sends out an email every day to a list on Constant Contact.
 * Version: 1.0.0
 * Author: Saddam Hossain Azad
 * Author URI: https://github.com/saddamazad
 */

define('EMAIL_ROOT', dirname(__FILE__));

define('EMAIL_FOLDER_URL', plugins_url('/', __FILE__));
require_once( EMAIL_ROOT . '/src/Ctct/autoload.php' );
require_once( EMAIL_ROOT . '/src/Ctct/ConstantContact.php' );
//require_once( EMAIL_ROOT . '/Tax-meta-class/Tax-meta-class.php');
require_once( EMAIL_ROOT . '/notifications.php' );
use Ctct\ConstantContact;

//$config = array(
//   'id' => 'tax_img_meta_box',
//   'title' => 'Image Box',
//   'pages' => array('quoteauthor'),
//   'context' => 'normal',
//   'fields' => array(),
//   'local_images' => false,  // Use local or hosted images (meta box images for add/remove)
//   'use_with_theme' => false //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
//   /* use_with_theme = get_stylesheet_directory_uri().'/Tax-meta-class/' for using in child theme */
//);
///*
//* Initiate taxonomy custom fields
//*/
//$tax_meta = new Tax_Meta_Class($config);
//$tax_meta->addText('quote_author_blog',array('name'=> 'Author Blog (URL) '));
//$tax_meta->addText('quote_author_twitter',array('name'=> 'Author Twitter (URL) '));
//$tax_meta->addImage('quote_author_photo',array('name'=> 'Author Image '));
//$tax_meta->Finish();

add_action( 'init', 'register_emails_custom_post_type' );
function register_emails_custom_post_type() {

	$labels = array(
		'name' => _x('Emails', 'Email name', 'RMTheme'),
		'singular_name' => _x('Email', 'Email singular name', 'RMTheme'),
		'add_new' => _x('Add New', 'Email', 'RMTheme'),
		'add_new_item' => __('Add New Email', 'RMTheme'),
		'edit_item' => __('Edit Email', 'RMTheme'),
		'new_item' => __('New Email', 'RMTheme'),
		'view_item' => __('View Email', 'RMTheme'),
		'search_items' => __('Search Email', 'RMTheme'),
		'not_found' => __('No Email Found', 'RMTheme'),
		'not_found_in_trash' => __('No Email Found in Trash', 'RMTheme'),
		'parent_item_colon' => ''
	);
	
	if ( current_user_can( 'manage_options' ) ) {
		/* A user with admin privileges */
		register_post_type('emails', array('labels' => $labels,
				'public' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'map_meta_cap' => true,			
				'hierarchical' => false,
				'publicly_queryable' => true,
				'query_var' => true,
				'exclude_from_search' => false,
				'rewrite' => array('slug' => 'email'),
				'show_in_nav_menus' => false,
				'supports' => array('title',  'editor', 'page-attributes', 'revisions')
			)
		);	
	} else {
		/* A user without admin privileges */
		register_post_type('emails', array('labels' => $labels,
				'public' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'capability_type' => array('email_post','email_posts'),
				'map_meta_cap' => true,			
				'hierarchical' => false,
				'publicly_queryable' => true,
				'query_var' => true,
				'exclude_from_search' => false,
				'rewrite' => array('slug' => 'email'),
				'show_in_nav_menus' => false,
				'supports' => array('title',  'editor', 'page-attributes', 'revisions')
			)
		);	
	}
		
}

//Add custom capibility for quote_manager
function leadership_email_manager_caps() {

		// Add the roles you'd like to administer the custom post types
		$roles = array('quote_manager','editor','administrator');
		
		// Loop through each role and assign capabilities
		foreach($roles as $the_role) { 

		     $role = get_role($the_role);
			
	             $role->add_cap( 'read' );
	             $role->add_cap( 'read_email_post');
	             $role->add_cap( 'read_private_email_posts' );
	             $role->add_cap( 'edit_email_post' );
	             $role->add_cap( 'edit_email_posts' );
	             $role->add_cap( 'edit_others_email_posts' );
	             $role->add_cap( 'edit_published_email_posts' );
	             $role->add_cap( 'publish_email_posts' );
	             $role->add_cap( 'delete_others_email_posts' );
	             $role->add_cap( 'delete_private_email_posts' );
	             $role->add_cap( 'delete_published_email_posts' );			 
	}
}
add_action( 'admin_init', 'leadership_email_manager_caps');


add_action('admin_menu' , 'daily_email_settings_page');
function daily_email_settings_page() {
	add_submenu_page( 'edit.php?post_type=emails', 'Daily Email Settings', 'Settings', 'read', basename(__FILE__), 'daily_email_settings');
	add_submenu_page( 'edit.php?post_type=emails', 'Generate Email', 'Generate Email', 'read', 'generate-email', 'func_generate_email_manually');
	add_submenu_page( 'edit.php?post_type=emails', 'Constant Contact Settings', 'Constant Contact Settings', 'read', 'cc_settings', 'constant_contact_settings');
}

function func_generate_email_manually(){
if(isset($_POST['generate_email_manually_submit'])){
	if(isset($_POST['generate_email_date'])){
		$generate_email_date = $_POST['generate_email_date'];
		$email_date = explode('-', $generate_email_date);
		$month = $email_date[0];
		$day   = $email_date[1];
		$year  = $email_date[2];		
		$generate_email_date = $day.'-'.$month.'-'.$year;		
		$generate_email_date = date("Y-m-d H:i:s", strtotime($generate_email_date));		
		$email_post_id = generate_daily_email($generate_email_date);	
	}else{
		$email_post_id = generate_daily_email();
	}	
	$email_post_edit_link = get_edit_post_link( $email_post_id );
	echo "<div class='updated'><p>Successfully Generated! <a href=".$email_post_edit_link.">Click Here To Edit Email</a></p></div>";
}
?>
<div class="wrap">
	<h2 style="padding-bottom: 25px;"><?php echo __('Generate Email Manually'); ?></h2>
	<form name="generate_email_manually" method="post" action="">
		<table class="form-table" style="margin-top:0;">
			<tr valign="top">
				<th scope="row" style="padding-top:0;"><?php echo __('Enter Date:'); ?></th>
				<td style="padding-top:0;">
					<input type="text" name="generate_email_date" value="" placeholder="MM-DD-YYYY" />
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="generate_email_manually_submit" class="button-primary" value="<?php _e('Generate') ?>" />
		</p>	
	</form>
	
</div>	
<?php
}


function daily_email_settings() {
	if(isset($_POST['email_options_submit'])){
		
		update_option( 'notified_emails', $_POST['notified_emails'] );
		update_option( 'notify_time_advance', $_POST['notify_time_advance'] );
		
		update_option('notification_email_subject', stripslashes_deep($_POST['notification_email_subject']));
		update_option('notification_email_message', stripslashes_deep($_POST['notification_email_message']));
		
		update_option('email_template', stripslashes_deep($_POST['email_template']));	
		
		echo "<div class='updated'><p>Successfully Updated</p></div>";
	}
?>
<div class="wrap">
	<h2 style="padding-bottom: 25px;"><?php echo __('Daily Email Settings'); ?></h2>
	<form name="daily_email_settings" method="post" action="">
		<table class="form-table" style="margin-top:0;">
			<tr valign="top">
				<th scope="row" style="padding-top:0;"><?php echo __('Who should get notified in advance of emails?'); ?></th>
				<td style="padding-top:0;">
					<input type="text" name="notified_emails" value="<?php echo get_option('notified_emails'); ?>" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php echo __('How long should we notify them in advance of it being sent?'); ?></th>
				<td>
					<input type="text" name="notify_time_advance" value="<?php echo get_option('notify_time_advance'); ?>" /> hours
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row"><label for="notification_email_subject">Notification Subject</label></th>
				<td><input type="text" name="notification_email_subject" id="notification_email_subject" value="<?php echo get_option('notification_email_subject'); ?>" class="regular-text" /><p class="description">Supported tags:<br />
					%%email-subject%% = (Generated Email Subject)<br />					
					</p></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="notification_email_message">Notification Message</label></th>
				<td><textarea name="notification_email_message" id="notification_email_message" class="large-text" rows="5"><?php echo stripslashes(get_option('notification_email_message')); ?></textarea><p class="description">Supported tags:<br />
					%%quote-email-subject%% = (Generated Email Subject From Quote)<br />
					%%approval-link%% = (Link Approve This Message )<br />
					%%edit-link%% = (Link Edit This Email)<br />
					%%quote-content%% = (Generated Quote Content)<br />
					%%sendout-date%% = (Send out date when quote email will be sent)					
					</p></td>
			</tr>	
			
			<tr valign="top">
				<th scope="row"><label for="email_template">Email Template</label></th>
				<td><textarea name="email_template" id="email_template" class="large-text" rows="20"><?php echo stripslashes_deep(get_option('email_template')); ?></textarea><p class="description">Supported tags:<br />
					%%content%% = (Auto generated text)<br />
					%%admin_email%% = ( Site admin email )<br />
					%%date%% = (Current Date)<br />					
					</p></td>
			</tr>	
		</table>

		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="notify_in_advance,how_long_should_notify,email_template" />
		
		<p class="submit">
			<input type="submit" name="email_options_submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>

<?php
}

function constant_contact_settings() {
?>
<div class="wrap">
	<h2 style="padding-bottom: 25px;"><?php echo __('Constant Contact Settings'); ?></h2>
	<?php
		if(isset($_POST['cc_options_submit'])){			
			update_option( 'test_email_address', $_POST['test_email_addr'] );			
			update_option( 'cc_api_key', $_POST['cc_api_key'] );
			update_option( 'cc_secret_key', $_POST['cc_secret_key'] );
			update_option( 'cc_access_token', $_POST['cc_access_token'] );
			update_option( 'cc_contact_list_id', $_POST['cc_contact_list_id'] );
	
			update_option('cc_email_template', stripslashes_deep($_POST['email_template']));
			echo "<div class='updated'><p>Successfully Updated</p></div>";
		}
		
		if(isset($_POST['cc_test_email_submit'])){
			if(isset($_POST['test_email_addr'])){						
				$email_address = $_POST['test_email_addr'];
				$email_post_id = $_POST['email_post'];
				send_cc_email_template($email_address, $email_post_id);
				echo "<div class='updated'><p>Email Sent! Please also check spam</p></div>";
			}
		}		
		
		
		
		
	?>	
	<form name="cc_email_testing" method="post" action="">
		<table class="form-table" style="margin-top:0;">	
	
			<tr valign="top">
				<th scope="row"><label for="test_email_addr">Send Test Email To: </label></th>
				<td><input type="text" name="test_email_addr" id="test_email_addr" value="" class="regular-text" placeholder="info@youremail.com" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cc_select_email">Select Email: </label></th>
				<td>				
				<?php
				$args = array(
					'post_type'  => 'emails',
					'post_status' => 'any',
					'orderby' => 'post_date',
					'order' => 'ASC',
					'date_query' => array(
						array(
							'after'     => date("F j, Y, g:i a"),
							'inclusive' => true,
						),
					),
					'posts_per_page' => -1,
				);
				$email_query = new WP_Query( $args );
				if ( $email_query->have_posts() ) {
					echo '<select name="email_post" id="email_post">';
					while ( $email_query->have_posts() ) {
						$email_query->the_post();
						echo '<option value="'.get_the_ID().'">' . get_the_title() . '</option>';
					}
					echo '</select>';
				}
				wp_reset_postdata();
				?>				
				
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"></th>
				<td><input type="submit" name="cc_test_email_submit" class="button-primary" value="<?php _e('Send Email Above Address!') ?>" /></td>				
			</tr>			
		</table>
	</form>	
	
	<br /><br /><br />
	
	<form name="cc_email_settings" method="post" action="">
		<table class="form-table" style="margin-top:0;">
			<tr valign="top">
				<th scope="row" style="padding-top:0;"><?php echo __('API KEY: '); ?></th>
				<td style="padding-top:0;">
					<input type="text" name="cc_api_key" value="<?php echo get_option('cc_api_key'); ?>" class="regular-text" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php echo __('SECRET KEY: '); ?></th>
				<td>
					<input type="text" name="cc_secret_key" value="<?php echo get_option('cc_secret_key'); ?>" class="regular-text" />
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row"><label for="cc_access_token">ACCESS TOKEN: </label></th>
				<td><input type="text" name="cc_access_token" id="cc_access_token" value="<?php echo get_option('cc_access_token'); ?>" class="regular-text" /></td>
			</tr>
			
			<tr valign="top">
				<th scope="row"><label for="cc_contact_list_id">Contact List: </label></th>
				<?php 
					$cc_contact_list_id = get_option('cc_contact_list_id'); 
					$cc_contact_list_ids = get_constant_contact_list_id();
				?>
				<td>
					<select id="cc_contact_list_id" name="cc_contact_list_id">
						<?php 
						foreach($cc_contact_list_ids as $cc_id=>$cc_name){
							echo '<option ';
							if($cc_id == $cc_contact_list_id) echo ' selected="selected" ';
							echo 'value="'.$cc_id.'">';
							echo $cc_name;
							echo '</option>';
						}
						?>
					</select>				
				</td>
			</tr>			
			
			
			
			<tr valign="top">
				<th scope="row"><label for="cc_access_token">Contact List Address: </label></th>
				<td>
					<?php
						$contacts = get_constant_contact_list();
						//print_r($contacts);
						foreach($contacts as $contact) {
							echo $contact.'<br />';
						}
					?>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="email_template">Email Template</label></th>
				<td><textarea name="email_template" id="email_template" class="large-text" rows="20"><?php echo stripslashes_deep(get_option('cc_email_template')); ?></textarea><p class="description">Supported tags:<br />
					%%logo%% = ( Generate website logo )<br />
					%%author_photo%% = ( Quote author photo )<br />
					%%QUOTETEXT%% = ( Generate the Quote )<br />
					%%QUOTEAUTHOR%% = ( Quote author name )<br />
					%%THOUGHTBEHIENDTHEQUOTE%% = ( Author's thought behind the quote )<br />
					</p></td>
			</tr>	
		</table>

		<p class="submit">
			<input type="submit" name="cc_options_submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>		
</div>

<?php
}

add_action( 'init', 'call_generate_daily_email');
function call_generate_daily_email(){
	if(isset($_GET['generate_email']) && ($_GET['generate_email'] == 'yes')){
		generate_daily_email();
	}
}


function generate_daily_email($email_date = ''){
		//check if alrady generated
		$existing_email_post_id = check_already_generated_email($email_date);
		if($existing_email_post_id){
			return $existing_email_post_id;
		}
		
		$manual_generate = 'no';
		if($email_date)
			$manual_generate = 'yes';
		
		$advance_hours = get_option('notify_time_advance');	
		$args = array(
					'post_type' => 'quote',
					'posts_per_page' => 1,
					'post_status' => 'publish',
					'meta_key' => '_rmt_quote_email',
					'meta_value' => 'yes',
					'meta_compare' => 'NOT EXISTS', // if the meta field does not exists or if the meta field not set yet
					'orderby' => 'rand'
				);
		$quote_posts = new WP_Query( $args );	
		if($quote_posts->have_posts()) {
			global $post;
			while($quote_posts->have_posts()): $quote_posts->the_post();
				$user_id = get_current_user_id();
				$email_content = apply_filters('the_content', get_post_field('post_content', $post->ID));
				$email_content = str_replace(array("\n", "\r"), '', $email_content);
				
				if($email_date)
					$post_title = date("l, F j, Y", strtotime($email_date));
				else	
					$post_title = date("l, F j, Y", strtotime('+'.$advance_hours.' hours')); 
					
					
				if($email_date)
					$email_scheduled_date = date("Y-m-d H:i:s", strtotime($email_date));
				else	
					$email_scheduled_date = date("Y-m-d H:i:s", strtotime('+'.$advance_hours.' hours')); 					
														
				$email_status = 'draft';
				if($manual_generate == 'yes')
						$email_status = 'future';
										
				$defaults = array(
							  'post_type'      => 'emails',
							  'post_title'     => $post_title,
							  'post_content'   => mysql_real_escape_string($email_content),
							  'post_status'    => $email_status,
							  'post_date'    => $email_scheduled_date,
							  'post_date_gmt'    => $email_scheduled_date,
							  'post_author'    => $user_id
							);
				if($post_id = wp_insert_post( $defaults )) {
					// add post meta data
					add_post_meta($post_id, '_rmt_quote_id', $post->ID);
					add_post_meta($post->ID, '_rmt_quote_email', 'yes');
	
					$encryptedKey = substr( md5( $post_id ), 0, 20 );
					add_post_meta($post_id, '_rmt_email_secret_key', $encryptedKey);
					if($manual_generate == 'no')
						rm_admin_notification($post_id);

				}
			endwhile;
			wp_reset_postdata();
		}
		
		return $post_id;
		
}

function get_constant_contact_list() {
	$cc_api_key = get_option('cc_api_key');
	$cc_secret_key = get_option('cc_secret_key');
	$cc_access_token = get_option('cc_access_token');
	$cc_contact_list_id = get_option('cc_contact_list_id');
	
	$cc = new ConstantContact($cc_api_key);
	
	$contacts = $cc->getContactsFromList($cc_access_token, $cc_contact_list_id);
	$results = $contacts->results;
	
	$emailArr = array();
	if(is_array($results)){
		foreach($results as $contact_details){
			//print_r($contact_details);
			$email_addresses = array();
			$email_addresses = $contact_details->email_addresses;
			foreach($email_addresses as $email_address){
				$emailArr[] = $email_address->email_address; 
			}
		}
	}
	return $emailArr;
}

function get_constant_contact_list_id() {
	$cc_ids = array();
	$cc_api_key = get_option('cc_api_key');
	$cc_secret_key = get_option('cc_secret_key');
	$cc_access_token = get_option('cc_access_token');
	
	$cc = new ConstantContact($cc_api_key);	
	
	$contacts_list = $cc->getLists($cc_access_token);

	if(is_array($contacts_list)){
		foreach($contacts_list as $contact_list_id){
			$cc_ids[$contact_list_id->id] = $contact_list_id->name;
			//echo $contact_list_id->name."( ".$contact_list_id->id." )<br />";
		}
	}
	
	return $cc_ids;
}

//Return email post id if exist FALSE otherwise
function check_already_generated_email($query_date = ''){
	$today = array();
	
	if($query_date){
		$time  = strtotime($query_date);
		$today['mday']   = date('d',$time);
		$today['mon'] = date('m',$time);
		$today['year']  = date('Y',$time);
	}else
		$today = getdate();
	
	$args = array(
		'post_type'  => 'emails',
		'post_status' => 'any',
		'orderby' => 'post_date',
		'order' => 'ASC',	
		'date_query' => array(
			array(
				'year'  => $today['year'],
				'month' => $today['mon'],
				'day'   => $today['mday'],
			),
		),
		'posts_per_page' => 1,
	);	
	
	$post_id = '';
	
	$email_query = new WP_Query( $args );
	if ( $email_query->have_posts() ) {
		while ( $email_query->have_posts() ) {
			$email_query->the_post();
			$post_id = get_the_ID();
		}
	}
	wp_reset_postdata();	
	
	return $post_id;			

}


add_action( 'wp', 'leadershipology_setup_schedule' );
/**
 * On an early action hook, check if the hook is scheduled - if not, schedule it.
 */
function leadershipology_setup_schedule() {
	if ( ! wp_next_scheduled( 'leadershipology_daily_event' ) ) {
		wp_schedule_event( time(), 'daily', 'leadershipology_daily_event');
	}
}


add_action( 'leadershipology_daily_event', 'leadershipology_do_this_daily' );
/**
 * On the scheduled action hook, run a function.
 */
function leadershipology_do_this_daily() {
	// do something everyday
	generate_daily_email();
}


/*
* do something on post status transition, from future to publish
*/
function on_publish_future_post( $post ) {
    // A function to perform when a scheduled post is published
	if( 'emails' == get_post_type( $post->ID ) ) {
		// send email to constant contact subscribers
		$contacts = get_constant_contact_list();
		foreach($contacts as $contact) {
			send_cc_email_template($contact, $post->ID);
		}
	}
}
add_action( 'future_to_publish',  'on_publish_future_post', 10, 1 );