<?php
/*
Plugin Name: WP True Typed
Plugin URI: http://darrinb.com/notes/2010/wp-true-typed/
Description: Anti-spam challenge question for your comment form.
Version: 1.5
Author: Darrin Boutote
Author URI: http://darrinb.com
*/
/*
Copyright 2010  Darrin Boutote  (contact : http://darrinb.com/hello/)
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Users of WP versions less than 3.0 will have to edit their comment form if they want the comment text to remain
 * <p><textarea name="comment" id="comment" cols="58" rows="10" tabindex="4"><?php echo stripslashes($_COOKIE['comment_author_comment_' . COOKIEHASH]);  ?></textarea></p>
 */


class wpTrueTyped {

	protected
		$wp_version;

	public function __construct(){
		$this->wp_version = absint(get_bloginfo('version'));		
		add_action('init', array(&$this, 'register_styles_frontend') );
		add_action('pre_comment_on_post', array(&$this, 'validate'));
		add_action('wp_enqueue_scripts', array(&$this,'add_styles_frontend'));
		add_action('wp_head', array( &$this, 'init'));		
	}

	public function init(){
		global $post;

		// Will not load if comments are closed or author is  already approved to comment
		if( !comments_open($post->ID) || $this->author_can_comment() ) {
			return;
		}

		if ( $this->wp_version < 3 ) {
			add_action('comment_form', array(&$this,'show_error_message'), 1);
			add_action('comment_form', array(&$this,'add_validation_field'), 2);			
		} else {
			add_action('comment_form_before_fields', array(&$this,'show_error_message'), 1);
			add_action('comment_form_after_fields', array(&$this,'add_validation_field'), 2);
			add_filter('comment_form_field_comment', array(&$this,'comment_field'), 1);
		}
	}

	
	// register stylesheets for front end
	public function register_styles_frontend(){
		wp_register_style(
			'ttype-front',
			plugins_url( 'style_truetyped.css', __FILE__ ),
			array(),
			'1.0.0',
			'all'
		);
	}
	
	
	// load CSS in the front end
	public function add_styles_frontend() {
		wp_enqueue_style( 'ttype-front' );
	}


	// Check if validation field needs to be loaded
	protected function author_can_comment() {
		if ( is_user_logged_in() ) {
			return true;
		}

		global $wpdb;
		$author = ( isset($_COOKIE['comment_author_'.COOKIEHASH]) ) ? stripslashes($_COOKIE['comment_author_' . COOKIEHASH]) : '';
		$email = ( isset($_COOKIE['comment_author_email_'.COOKIEHASH]) ) ? stripslashes($_COOKIE['comment_author_email_' . COOKIEHASH]) : '';

		if ( '' !== $author && '' !== $email ) {
			$ok_to_comment = $wpdb->get_var(
				$wpdb->prepare(
					"
					SELECT comment_approved
					FROM $wpdb->comments
					WHERE 1=1
					AND comment_author = %s
					AND comment_author_email = %s
					AND comment_approved = %s
					",
					$author,
					$email,
					'1'
				)
			);

			if ( '1' === $ok_to_comment ) {
				return true;
			} else {
				return false;
			}
		}

		return false;
	}


	// utility function to grab the $_GET or $_POST parameter
	public function get_param($param, $default='') {
		return (isset($_POST[$param])?$_POST[$param]:(isset($_GET[$param])?$_GET[$param]:$default));
	}

	
	// Generate error message
	public function show_error_message() {
		if( 'comm-err' === $this->get_param('m') ) {
			echo '<p id="comment-form-error-msg" class="comment-form-error">Error: the validation you entered is incorrect.</p>';
		}
		echo '';
	}

	
	// Create validation field for Comment Form
	public function add_validation_field() {		
		if ( $this->wp_version < 3 ) { ?>
			<p class="comment-form-validation">
				<label for="validate"><small>Validation (Enter <strong>first word</strong> of <strong>post title</strong>, no characters or numbers.)(required)</small></label>
				<input id="validate" type="text" size="22" value="" name="validate" aria-required="true" tabindex="6" />
			</p>
		<?php } else { ?>
			<p class="comment-form-validation">
				<label for="validate">Validation (Enter the <strong>first word</strong> of the <strong>post title</strong>, no characters or numbers.) <span class="required">*</span></label>
				<input id="validate" type="text" size="30" value="" name="validate" aria-required="true" />
			</p>
		<?php };
	}

	
	// Modify the Comment Textarea (WP v3+)
	public function comment_field() {
		$comment_cookie = ( isset($_COOKIE['comment_author_comment_' . COOKIEHASH]) ) ? $_COOKIE['comment_author_comment_' . COOKIEHASH] : '';
		$comment_field = '<p class="comment-form-comment"><label for="comment">' . _x( 'Comment', 'noun' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true">'. esc_attr($comment_cookie) .'</textarea></p>';
		return $comment_field;
	}
	

	// Validate
	public function validate() {

		// Will not execute if author is pre-approved to comment
		if( $this->author_can_comment() ) {
			return;
		}

		// Unset any cookies previously set by the validation script
		if ( isset($_COOKIE['comment_author_comment_'.COOKIEHASH]) ) {
			setcookie('comment_author_comment_' . COOKIEHASH, '', time() - 60, COOKIEPATH, COOKIE_DOMAIN);
		};

		// Get the ID of the Post    
		$comment_post_ID = $this->get_param('comment_post_ID', 0);
		
		// Get the first word of the post title
		$post_title = get_the_title($comment_post_ID);                                      // get the post title
		$post_title_first_word = $this->sanitize_for_comparison($post_title);

		// Init a variable for the validation word the user entered
		$comment_validation = $this->get_param('validate');
		$comment_validation_first_word = $this->sanitize_for_comparison($comment_validation);
		
		// Validation / Redirection
		if ( $comment_validation_first_word !== $post_title_first_word ) {
		
			// Set cookies for Comment Form Fields and Comment Text
			$comment_cookie_lifetime = apply_filters('comment_cookie_lifetime', 30000000);
			setcookie('comment_author_' . COOKIEHASH, $_POST['author'], time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
			setcookie('comment_author_email_' . COOKIEHASH, $_POST['email'], time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
			setcookie('comment_author_url_' . COOKIEHASH, esc_url($_POST['url']), time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
			setcookie('comment_author_comment_' . COOKIEHASH, stripslashes(htmlentities($_POST['comment'], ENT_QUOTES, 'utf-8' )), time() + 60, COOKIEPATH, COOKIE_DOMAIN);

			// Check for Comment Parent        
			$comment_parent = (int)$this->get_param('comment_parent', 0);

			// Redirect back to Post page
			$ref = remove_query_arg( array('m', 'replytocom'), wp_get_referer() );
			$ref = $ref.'#comment-form-error-msg';
			$ref = ( $comment_parent > 0 ) ? add_query_arg (array('replytocom' => $comment_parent , 'm' => 'comm-err'), $ref) : add_query_arg ('m', 'comm-err', $ref) ;
			wp_redirect($ref);
			exit;
			
		}
		
	}	
	

	// utility function to sanitize input to check post title vs. validation entered
	protected function sanitize_for_comparison($var){
		$var = trim($var);													  // trim
		$var = strip_tags($var); 											  // strip any tags
		$var = preg_replace('#[^A-Za-z\s\s+]#', '', $var);                    // strip everything but letters and spaces
		$var = explode(' ', $var);                                            // break at spaces (creates array)
		foreach ($var as $key => $value) {                                    // strip any spaces from array
			if ( is_null($value) || '' === $value ) {
				unset($var[$key]);
			}
		}

		$var = array_values($var);                                            // reorder array keys
		$var_first_word = isset($var[0]) ? $var[0] : substr(rand(), 0, 7);    // get the first word of input
		$var_first_word = preg_replace('#[^A-Za-z]#', '', $var_first_word);   // strip everything but letters
		$var_first_word = strtolower($var_first_word);                        // lowercase it
		$var_first_word = md5($var_first_word);                               // hash it
		
		return $var_first_word;
	}
	
}

$wp = new wpTrueTyped();

?>