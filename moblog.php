<?php

/*
Plugin Name: Moblog
Plugin URI: http://github.com/benthebear/moblog/
Description: Mobile Blogging via the mighty mighty Email
Author: Benjamin Birkenhake
Version: 2.0.1
Author URI: http://birkenhake.org/
*/

require_once("email.class.php");
require_once("moblog.config.php");   


function moblog_get_mails(){

	$mail = new email(MOBLOG_SERVER, MOBLOG_PORT, MOBLOG_EMAIL, MOBLOG_PASSWORD, MOBLOG_PATH);
	$mail->get_all_mail();
	$counter = 1;
	// Go through all Mails
	while ($mail->mails[$counter]["date"]!=""){
		//Create an Post-Array for every Mail
		$post = array();
		$post['post_type'] = "post";
		$post['post_status'] = "publish";
		// Get da Title of the Mail
		$post['post_title'] = $mail->mails[$counter]["subject"];
		// Get da Body of da Mail
		$post['post_content'] = "";
		$post['post_author'] = "1";
		// $post['post_content'] .= $mail->mails[$counter]["text"];
		// For some reasons, the Image Counter always starts with 2
		$pic = 2;
		$piccounter = 0;
		while ($mail->mails[$counter]["image"][$pic]["name"]!=""){
			$post['post_content'] .= "<p class=\"moblogImage\"><img src=\"/wp-content/uploads/moblog-w880i/".$mail->mails[$counter]["image"][$pic]["name"]."\"  /></p>";
			$pic++;
			$piccounter++;
		}
		// Save the Mail
		$post_id = wp_insert_post($post);
		wp_set_post_terms($post_id, '414', 'category');
		$counter++;
	}
	// Delete all Mails
	$mail->delete_all_mail();
}

?>