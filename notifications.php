<?php
//approve email post
function approve_email_post() {
	if( isset($_GET['approve_post_id']) && $_GET['approve_post_id'] != '' ) {
		$secret_key = $_GET['secret_key'];
		if(!empty($secret_key)) {
			$output = '';
			$email_secret_key = get_post_meta($_GET['approve_post_id'], '_rmt_email_secret_key', true);
			if( $secret_key == $email_secret_key ) {
				// Update post_status of the post bearing id $_GET['approve_post_id']
				$args = array(
				  'ID' => $_GET['approve_post_id'],
				  'post_status' => 'future'
				);
				wp_update_post( $args );

				add_filter('the_content ', 'success_msg');
				function success_msg($content) {
					$content = do_shortcode( "[success title = 'Congrats!']<p>Email approved/published.</p>[/success]" );
					return $content;
				}

  			} else {
				add_filter('the_content ', 'error_msg');
				function error_msg($content) {
					$content = do_shortcode( "[errors title = 'Alert!']<p>You are not authorized to approve this email.</p>[/errors]" );
					return $content;
				}
			}
		}
	}
}
add_action('init', 'approve_email_post');


//admin notification
function rm_admin_notification($email_post_id){
	$email_subject = get_option('notification_email_subject');		
	$email_message = get_option('notification_email_message');	
	
	$quote_id = get_post_meta($email_post_id, '_rmt_quote_id', true);
	$quote_title = get_the_title( $quote_id );		

	$subject_search = array();
	$subject_replace = array();	
	
	$subject_search[] = '%%email-subject%%';
	$subject_replace[] = get_the_title( $quote_id );
	$email_subject = str_replace($subject_search, $subject_replace, $email_subject);	

	$search = array();
	$replace = array();	

	$search[] = '%%quote-email-subject%%';
	$replace[] = get_the_title( $quote_id );
	
	$search[] = '%%sendout-date%%';
	$replace[] = get_the_time('l, F j, Y', $email_post_id);	

	$search[] = '%%approval-link%%';
	$secret_key = get_post_meta($email_post_id, '_rmt_email_secret_key', true);
	$replace[] = home_url().'?approve_post_id='.$email_post_id.'&secret_key='.$secret_key;

	$search[] = '%%edit-link%%';
	$replace[] = admin_url( 'post.php?post='.$email_post_id.'&action=edit');
	
	$search[] = '%%quote-content%%';
	$email_quote_content = apply_filters('the_content', get_post_field('post_content', $email_post_id));
	//$replace[] = mysql_real_escape_string($email_quote_content);
	$replace[] = $email_quote_content;


	$get_message = str_replace($search, $replace, $email_message);	
	$get_message = 	stripslashes($get_message);
	$get_message = str_replace(array("\n", "\r"), '', $get_message);
	//$get_message = nl2br($get_message);
	
	$admin_emails = get_option('notified_emails');
	$admin_email_arr = explode(',', $admin_emails);
	foreach($admin_email_arr as $user_email){
		rm_notification(trim($user_email), $email_subject, $get_message);
	}
}






//Send email with custom email template
function rm_notification($user_email, $email_subject, $message){
	//process email template
	$email_message_template = get_option('email_template');
	$search = array();
	$replace = array();		
	$search[] = '%%content%%';
	$replace[] = $message;			
	$search[] = '%%date%%';
	$replace[] = date("F j, Y, g:i a");		
	$search[] = '%%admin_email%%';
	$replace[] = get_option('admin_email');			
	//$search[] = '%%blog_url%%';
	//$replace[] = home_url();			
	$message_send = str_replace($search, $replace, $email_message_template);	
	$message_send = stripslashes($message_send);		
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\r\n";
	add_filter('wp_mail_content_type',create_function('', 'return "text/html"; '));			
	@wp_mail( $user_email, $email_subject, $message_send, $headers );	
}	


function send_cc_email_template($email_address, $email_post_id) {
	$current_quote_id = get_post_meta($email_post_id, '_rmt_quote_id', true);
	//process email template
	$email_message_template = get_option('cc_email_template');
	
	$email_subject = 'Leadershipology Quote: '. get_the_title( $current_quote_id );
	
	$search = array();
	$replace = array();
					
	$search[] = '%%logo%%';
	$replace[] = '<a style="border: none;" href="'.home_url().'" class="buttonlink"><img width="400" height="160" border="0" src="'.EMAIL_FOLDER_URL.'images/leadershipology-logo.jpg"></a>';

	$search[] = '%%author_photo%%';
	$replace[] = '<img src="'.EMAIL_FOLDER_URL.'images/200x160_PK.jpg" alt="" />';

	$search[] = '%%quote_title%%';
	$replace[] = get_the_title( $current_quote_id );
	
	$search[] = '%%QUOTETEXT%%';
	$content_quote = get_post($email_post_id);
	$content_quote_text = $content_quote->post_content;
	$content_quote_text = apply_filters('the_content', $content_quote_text);
	$content_quote_text = str_replace(array('<p>', '</p>'), '', $content_quote_text);
	$replace[] = $content_quote_text;

	$search[] = '%%QUOTEAUTHOR%%';
	$replace[] = get_single_term($current_quote_id, 'quoteauthor');
	
	$search[] = '%%THOUGHTBEHIENDTHEQUOTE%%';
	$replace[] = get_post_meta($current_quote_id, '_cmb_thought_behind_quote', true);

	$message_send = str_replace($search, $replace, $email_message_template);
	$message_send = stripslashes($message_send);
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\r\n";
	add_filter('wp_mail_content_type',create_function('', 'return "text/html"; '));
	
	@wp_mail( $email_address, $email_subject, $message_send, $headers );
}


function get_single_term($post_id, $taxonomy) {
    $terms = wp_get_object_terms($post_id, $taxonomy);
    if(!is_wp_error($terms)) {
        return $terms[0]->name;
    }
}