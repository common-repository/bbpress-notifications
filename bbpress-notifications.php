<?php
/*
Plugin Name: bbPress Notifications
Description: bbPress Notifications allows you to send email notifications to specific users when new topics or replies are posted.
Version: 1.0.1.1
Author: dFactory
Author URI: http://www.dfactory.eu/
Plugin URI: http://www.dfactory.eu/plugins/bbpress-notifications
License: MIT License
License URI: http://opensource.org/licenses/MIT

bbPress Notifications
Copyright (C) 2013, Digital Factory - info@digitalfactory.pl

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class bbPress_Notifications
{
	
	public $_options = array();
	
	public function __construct()
	{
		
		$this->_options = array(
			'bbpress_notifications_new_topic_recipients' => get_option('admin_email'),
			'bbpress_notifications_new_topic_email_subject' => __('[[blogname]] New topic: [topic-title]', 'bbpress-notifications'),
			'bbpress_notifications_new_topic_email_body' => __("Hello!\n\nA new topic has been posted by [topic-author].\n\nTopic title: [topic-title]\nTopic url: [topic-url]\n\nTopic excerpt:\n[topic-excerpt]\n\nNotify message by bbPress Notifications", 'bbpress-notifications'),
			'bbpress_notifications_new_reply_recipients' => get_option('admin_email'),
			'bbpress_notifications_new_reply_email_subject' => __('[[blogname]] New reply to: [topic-title]', 'bbpress-notifications'),
			'bbpress_notifications_new_reply_email_body' => __("Hello!\n\nA new reply has been posted by [reply-author].\n\nTopic title: [topic-title]\nReply url: [reply-url]\n\nReply excerpt:\n[reply-excerpt]\n\nNotify message by bbPress Notifications", 'bbpress-notifications'),
		);

		//activation hook
		register_activation_hook(__FILE__, array($this, 'activate_plugin'));
		
		//actions
		add_action('plugins_loaded', array($this, 'load_textdomain'));
		add_action('admin_init', array($this, 'admin_settings'), 16);
		add_action('bbp_new_topic', array($this, 'send_new_topic_notification'), 10, 4);
		add_action('bbp_new_reply', array($this, 'send_new_reply_notification'), 10, 5);
		
		//filters
	}
	
	/**
	 * Load textdomain
	*/
	public function load_textdomain()
	{
		load_plugin_textdomain('bbpress-notifications', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
	}
	
	/**
	 * Plugin installation method
	 */
	public function activate_plugin()
	{
		
		//die if bbPress is not active
		if(!class_exists('bbPress'))
		{
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die(__('Sorry, you need to activate bbPress first.', 'bbpress-notifications'));
		}
		
		$options = $this->_options;
		// print_r($_options);exit;
		
		//loop through default options and add them into DB
		foreach($options as $option => $value)
		{
			update_option($option, $value);
		}
	}
	
	/**
	 * Setup the settings on the main bbPress forum settings page
	 */
	public function admin_settings()
	{
		//add the section to primary bbPress options
		add_settings_section('bbpress_notifications_options', __('E-mail Notifications', 'bbpress-notifications'), array($this, 'notifications_section_heading'), 'bbpress');

		//settings fields
		add_settings_field('bbpress_notifications_new_topic_recipients', __('New Topic Email Addresses', 'bbpress-notifications'), array($this, 'email_topic_addresses_textarea'), 'bbpress', 'bbpress_notifications_options');
		add_settings_field('bbpress_notifications_new_topic_email_subject', __('New Topic Email Subject', 'bbpress-notifications'), array($this, 'email_topic_subject_text'), 'bbpress', 'bbpress_notifications_options');
		add_settings_field('bbpress_notifications_new_topic_email_body', __('New Topic Email Body', 'bbpress-notifications'), array($this, 'email_topic_body_textarea'), 'bbpress', 'bbpress_notifications_options');
		add_settings_field('bbpress_notifications_new_reply_recipients', __('New Reply Email Addresses', 'bbpress-notifications'), array($this, 'email_reply_addresses_textarea'), 'bbpress', 'bbpress_notifications_options');
		add_settings_field('bbpress_notifications_new_reply_email_subject', __('New Reply Email Subject', 'bbpress-notifications'), array($this, 'email_reply_subject_text'), 'bbpress', 'bbpress_notifications_options');
		add_settings_field('bbpress_notifications_new_reply_email_body', __('New Reply Email Body', 'bbpress-notifications'), array($this, 'email_reply_body_textarea'), 'bbpress', 'bbpress_notifications_options');

		//register our settings with the bbPress forum settings page
		register_setting('bbpress', 'bbpress_notifications_new_topic_recipients', array($this, 'validate_email_addresses'));
		register_setting('bbpress', 'bbpress_notifications_new_topic_email_subject', array($this, 'validate_topic_email_subject'));
		register_setting('bbpress', 'bbpress_notifications_new_topic_email_body', array($this, 'validate_topic_email_body'));
		register_setting('bbpress', 'bbpress_notifications_new_reply_recipients', array($this, 'validate_email_addresses'));
		register_setting('bbpress', 'bbpress_notifications_new_reply_email_subject', array($this, 'validate_reply_email_subject'));
		register_setting('bbpress', 'bbpress_notifications_new_reply_email_body', array($this, 'validate_reply_email_body'));
	}
	
	/**
	 * Output the new topic notification section in the bbPress forum settings
	 */
	public function notifications_section_heading() 
	{
		_e('Configure e-mail notifications when new topics and/or replies are posted.', 'bbpress-notifications');
	}
	
	/**
	 * Output the textarea of email addresses
	 */
	public function email_topic_addresses_textarea()
	{
		$email_addresses = get_option('bbpress_notifications_new_topic_recipients');
		$email_addresses = str_replace(' ', "\n", $email_addresses);

		echo '<textarea id="bbpress_notifications_new_topic_recipients" cols="50" rows="2" name="bbpress_notifications_new_topic_recipients">'.$email_addresses.'</textarea>';
		echo '<p>'.__('Email addresses to be notified when a new topic is posted. One per line.', 'bbpress-notifications').'</p>';
	}
	
	public function email_reply_addresses_textarea()
	{
		$email_addresses = get_option('bbpress_notifications_new_reply_recipients');
		$email_addresses = str_replace(' ', "\n", $email_addresses);

		echo '<textarea id="bbpress_notifications_new_reply_recipients" cols="50" rows="2" name="bbpress_notifications_new_reply_recipients">'.$email_addresses.'</textarea>';
		echo '<p>'.__('Email addresses to be notified when a new reply is posted. One per line.', 'bbpress-notifications').'</p>';
	}
	
	/**
	 * Output the text of email subject
	 */
	public function email_topic_subject_text()
	{
		$email_subject = get_option('bbpress_notifications_new_topic_email_subject');
		$shortcodes = '[blogname], [topic-title], [topic-content], [topic-excerpt], [topic-url], [topic-author]';
		
		echo '<input type="text" id="bbpress_notifications_new_topic_email_subject" size="40" name="bbpress_notifications_new_topic_email_subject" value="'.$email_subject.'">';
		echo '<p>'.__('Shortcodes:', 'bbpress-notifications').' '.$shortcodes.'</p>';
	}
	
	public function email_reply_subject_text()
	{
		$email_subject = get_option('bbpress_notifications_new_reply_email_subject');
		$shortcodes = '[blogname], [reply-title], [reply-content], [reply-excerpt], [reply-url], [reply-author], [topic-title], [topic-url], [topic-author]';
		
		echo '<input type="text" id="bbpress_notifications_new_reply_email_subject" size="40" name="bbpress_notifications_new_reply_email_subject" value="'.$email_subject.'">';
		echo '<p>'.__('Shortcodes:', 'bbpress-notifications').' '.$shortcodes.'</p>';
	}
	
	/**
	 * Output the textarea of email addresses
	 */
	public function email_topic_body_textarea()
	{
		$email_body = get_option('bbpress_notifications_new_topic_email_body');
		$shortcodes = '[blogname], [topic-title], [topic-content], [topic-excerpt], [topic-url], [topic-author]';
		
		echo '<textarea id="bbpress_notifications_new_topic_email_body" cols="50" rows="6" name="bbpress_notifications_new_topic_email_body">'.$email_body.'</textarea>';
		echo '<p>'.__('Shortcodes:', 'bbpress-notifications').' '.$shortcodes.'</p>';
	}
	
	public function email_reply_body_textarea()
	{
		$email_body = get_option('bbpress_notifications_new_reply_email_body');
		$shortcodes = '[blogname], [reply-title], [reply-content], [reply-excerpt], [reply-url], [reply-author], [topic-title], [topic-url], [topic-author]';
		
		echo '<textarea id="bbpress_notifications_new_reply_email_body" cols="50" rows="6" name="bbpress_notifications_new_reply_email_body">'.$email_body.'</textarea>';
		echo '<p>'.__('Shortcodes:', 'bbpress-notifications').' '.$shortcodes.'</p>';
	}
	
	/**
	 * Validate email addresses
	 */
	public function validate_email_addresses($email_addresses)
	{
		// Make array out of textarea lines
		$valid_addresses = '';
		$recipients = str_replace(' ', "\n", $email_addresses);
		$recipients = explode("\n", $recipients);

		// Check validity of each address
		foreach ($recipients as $recipient) {
			if (is_email(trim($recipient)))
				$valid_addresses .= $recipient . "\n";
		}

		// Trim off extra whitespace
		$valid_addresses = trim($valid_addresses);
		
		return $valid_addresses;
	}
	
	/**
	 * Validate email subject
	 */
	public function validate_topic_email_subject($email_subject)
	{
		$email_subject = !empty($email_subject) ? esc_html($email_subject) : $this->_options['bbpress_notifications_new_topic_email_subject'];
		return $email_subject;
	}
	public function validate_reply_email_subject($email_subject)
	{
		$email_subject = !empty($email_subject) ? esc_html($email_subject) : $this->_options['bbpress_notifications_new_reply_email_subject'];
		return $email_subject;
	}
	
	/**
	 * Validate email body
	 */
	public function validate_topic_email_body($email_body)
	{
		$email_body = !empty($email_body) ? esc_html($email_body) : $this->_options['bbpress_notifications_new_topic_email_body'];
		return $email_body;
	}
	public function validate_reply_email_body($email_body)
	{
		$email_body = !empty($email_body) ? esc_html($email_body) : $this->_options['bbpress_notifications_new_reply_email_body'];
		return $email_body;
	}
	
	/**
	 * Get the New Topic recipients
	 */
	public function get_new_topic_recipients()
	{
		// Get recipients and turn into an array
		$recipients = get_option('bbpress_notifications_new_topic_recipients');
		$recipients = str_replace(' ', "\n", $recipients);
		$recipients = explode("\n", $recipients);
		$recipients = array_values($recipients);
		
		return $recipients;
	}
	
	/**
	 * Get the New Reply recipients
	 */
	public function get_new_reply_recipients()
	{
		// Get recipients and turn into an array
		$recipients = get_option('bbpress_notifications_new_reply_recipients');
		$recipients = str_replace(' ', "\n", $recipients);
		$recipients = explode("\n", $recipients);
		$recipients = array_values($recipients);
		
		return $recipients;
	}
	
	/**
	 * Send the notification e-mails on new topic to the addresses defined in options
	 */
	public function send_new_topic_notification($topic_id = 0, $forum_id = 0, $anonymous_data = false, $topic_author = 0) 
	{
		
		//grab stuff we will be needing for the email
		$recipients = $this->get_new_topic_recipients();
		$blogname  = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$topic_title = html_entity_decode(strip_tags(bbp_get_topic_title($topic_id)), ENT_NOQUOTES, 'UTF-8');
		$topic_content = html_entity_decode(strip_tags(bbp_get_topic_content($topic_id)), ENT_NOQUOTES, 'UTF-8');
		$topic_excerpt = html_entity_decode(strip_tags(bbp_get_topic_excerpt($topic_id, 100)), ENT_NOQUOTES, 'UTF-8');
		$topic_author = bbp_get_topic_author($topic_id);
		$topic_url = bbp_get_topic_permalink($topic_id);

		//get the template
		$email_subject = get_option('bbpress_notifications_new_topic_email_subject');
		$email_body = get_option('bbpress_notifications_new_topic_email_body');

		//swap out the shortcodes with useful information :)
        $find = array('[blogname]', '[topic-title]', '[topic-content]', '[topic-excerpt]', '[topic-author]', '[topic-url]');
        $replace = array($blogname, $topic_title, $topic_content, $topic_excerpt, $topic_author, $topic_url);
		$email_subject = str_replace($find, $replace, $email_subject);
        $email_body = str_replace($find, $replace, $email_body);

		if (!empty($recipients)) 
		{
			//send email to each user
			foreach ($recipients as $recipient)
			{
				@wp_mail($recipient, $email_subject, $email_body);
			}
		}
	}
	
	/**
	 * Send the notification e-mails on new reply to the addresses defined in options
	 */
	public function send_new_reply_notification($reply_id = 0, $topic_id = 0, $forum_id = 0, $anonymous_data = false, $reply_author = 0) 
	{
		
		//grab stuff we will be needing for the email
		$recipients = $this->get_new_reply_recipients();
		$blogname  = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$reply_title = html_entity_decode(strip_tags(bbp_get_reply_title($reply_id)), ENT_NOQUOTES, 'UTF-8');
		$reply_content = html_entity_decode(strip_tags(bbp_get_reply_content($reply_id)), ENT_NOQUOTES, 'UTF-8');
		$reply_excerpt = html_entity_decode(strip_tags(bbp_get_reply_excerpt($reply_id, 100)), ENT_NOQUOTES, 'UTF-8');
		$reply_author = bbp_get_reply_author($reply_id);
		$reply_url = bbp_get_reply_permalink($reply_id);
		$topic_title = html_entity_decode(strip_tags(bbp_get_topic_title($topic_id)), ENT_NOQUOTES, 'UTF-8');
		$topic_author = bbp_get_topic_author($topic_id);
		$topic_url = bbp_get_topic_permalink($topic_id);

		//get the template
		$email_subject = get_option('bbpress_notifications_new_reply_email_subject');
		$email_body = get_option('bbpress_notifications_new_reply_email_body');

		//swap out the shortcodes with useful information :)
        $find = array('[blogname]', '[reply-title]', '[reply-content]', '[reply-excerpt]', '[reply-author]', '[reply-url]', '[topic-title]', '[topic-author]', '[topic-url]');
        $replace = array($blogname, $reply_title, $reply_content, $reply_excerpt, $reply_author, $reply_url, $topic_title, $topic_author, $topic_url);
		$email_subject = str_replace($find, $replace, $email_subject);
        $email_body = str_replace($find, $replace, $email_body);

		if (!empty($recipients)) 
		{
			//send email to each user
			foreach ($recipients as $recipient)
			{
				@wp_mail($recipient, $email_subject, $email_body);
			}
		}
	}
	
}

$bbpress_notifications = new bbPress_Notifications();