<?php
/**
 *
 * @package ESIG_AAMS_Admin
 * @author LDS
 */
if( ! class_exists( 'ESIG_CUSTOM_MESSAGE_WITH_SHORTCODE' ) ) :

	class ESIG_CUSTOM_MESSAGE_WITH_SHORTCODE {
		/*
		 * Instance of this class
		 * @since  1.0
		 * @var    object
		 */
		protected static $instance = null;


		/**
		 * Slug of the plugin screen.
		 * @since    1.0
		 * @var      string
		 */
		protected $plugin_screen_hook_suffix = null;

		const CUSTOM_MESSAGE_SHORTCODE = 'esig_custom_message_shortcode';
		const CUSTOM_MESSAGE_SHORTCODE_TEXT = 'esig_custom_message_shortcode_text';
		const CUSTOM_MESSAGE_SHORTCODE_CODE = 'esig_custom_message_shortcode_code';
	//	const CONFIRM_CUSTOM_MESSAGE = 'confirmation_custom_message';
	//	const CONFIRM_CUSTOM_MESSAGE_TEXT = 'confirmation_custom_message_text';

		/**
		 * Initialize the plugin by loading admin scripts & styles and adding a
		 * settings page and menu.
		 * @since     1.0
		 */
		private function __construct() {
		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$this->plugin_slug = 'esig-custom-message-with-shortcode';
		// Load admin style sheet and JavaScript.
		// Load admin style sheet and JavaScript.
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		// Add an action link pointing to the options page.
		add_filter('esig_admin_more_document_contents', array($this, 'document_add_datas'), 10, 1);
			// adding action
		add_action('esig_document_after_save', array($this, 'custom_message_after_save'), 10, 1);
		add_action('esig_sad_document_invite_send', array($this, 'sad_document_after_save'), 10, 1);

		add_filter('esig-invite-custom-message', array($this, 'invite_custom_message'), 10, 2);
		//Formidable form
		add_action( 'frm_after_create_entry', array( $this, 'set_pdf_id' ), 30, 2 );
		}

		/**
		 * Return an instance of this class.
		 * @since     0.1
		 * @return    object    A single instance of this class.
		 */
		public static function instance() {

			// If the single instance hasn't been set, set it now.
			if (null == self::$instance) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		public function sad_document_after_save($args) {

			$doc_id = $args['document']->document_id;
			$old_doc_id = $args['old_doc_id'];

			if ($this->isEnabled($old_doc_id)) {
				$this->saveCustomMessage($doc_id, $this->getCustomMessage($old_doc_id));
				$this->saveCustomMessageText($doc_id, $this->getCustomMessageText($old_doc_id));
				$this->saveCustomMessageShortcode($doc_id, $this->getCustomMessageShortcode($old_doc_id));
			}
		}

		public function isEnabled($docId) {
			if ($this->getCustomMessage($docId)) {
				return true;
			}
			return false;
		}
		public function getCustomMessage($docId) {
			$customMessage = WP_E_Sig()->meta->get($docId, self::CUSTOM_MESSAGE_SHORTCODE);
			if ($customMessage) {
				return $customMessage;
			}
			return WP_E_Sig()->setting->get_generic(self::CUSTOM_MESSAGE_SHORTCODE . $docId);
		}

		public function getCustomMessageText($docId) {
			$customMessageText = WP_E_Sig()->meta->get($docId, self::CUSTOM_MESSAGE_SHORTCODE_TEXT);
			if ($customMessageText) {
				return html_entity_decode($customMessageText);
			}
			return WP_E_Sig()->setting->get_generic(self::CUSTOM_MESSAGE_SHORTCODE_TEXT . $docId);
		}

		public function getCustomMessageShortcode($docId) {
			$customMessageShortcode = WP_E_Sig()->meta->get($docId, self::CUSTOM_MESSAGE_SHORTCODE_CODE);
			if ($customMessageShortcode) {
				return html_entity_decode($customMessageShortcode);
			}
			return WP_E_Sig()->setting->get_generic(self::CUSTOM_MESSAGE_SHORTCODE_CODE . $docId);
		}



		public function saveCustomMessage($docId, $value) {
			WP_E_Sig()->meta->add($docId, self::CUSTOM_MESSAGE_SHORTCODE, $value);
		}

		public function saveCustomMessageText($docId, $value) {
			WP_E_Sig()->meta->add($docId, self::CUSTOM_MESSAGE_SHORTCODE_TEXT, esc_attr($value));
		}

		public function saveCustomMessageShortcode($docId, $value) {
			WP_E_Sig()->meta->add($docId, self::CUSTOM_MESSAGE_SHORTCODE_CODE, esc_attr($value));
		}
		public function custom_message_after_save($args) {
			$docId = $args['document']->document_id;
			$this->saveCustomMessage($docId, esigpost('esig_custom_message_shortcode'));
			$this->saveCustomMessageText($docId, esigpost('esig_custom_message_shortcode_text'));
			$this->saveCustomMessageShortcode($docId, esigpost('esig_custom_message_shortcode_code'));

		}

		/**
		 * Filter:
		 * Adds options to the document-add and document-edit screens
		 */
		public function document_add_datas($more_contents) {
			//echo "test";
			//$doc_type = $api->document->getDocumenttype($document_id) ;
			$checked = '';
			$custom_text = '';
			$shortcode = '';
			$confText = '';

			// if document is not basic document return
			$doc_id = ESIG_GET('document_id');

			if ($this->isEnabled($doc_id)) {
				$checked = 'checked';
				$text = $this->getCustomMessageText($doc_id);
				$shortcode = $this->getCustomMessageShortcode($doc_id);
				$custom_text = stripcslashes($text);
			} else {
				$custom_text = '';
			}
			$assets_url = ESIGN_ASSETS_DIR_URI;
			$more_contents .= '<strong>Formidable PDF Download Enable Options</strong>
					<p id="esig_custom_message_optionx">
						<a href="#" class="tooltip">
								<img src="' . $assets_url . '/images/help.png" height="20px" width="20px" align="left" />
								<span>
								' . __('Selecting this option allows you to easily insert a custom message with formidable shortcode to download pdf in signer invitation e-mail', 'esig') . '
								</span>
						</a>
						<input type="checkbox" ' . $checked . ' id="esig_custom_message" name="esig_custom_message_shortcode" value="1">
						<label class="leftPadding-5">' . __('Add custom formidable shortcode to signer invite email', 'esig') . '</label>
						<div id="esig-custom-message-input" style="display:block;padding-left:50px;">
						<textarea name="esig_custom_message_shortcode_text" cols="100" rows="8" placeholder="' . __('Add a custom comment that will be inserted formidable shortcode to download pdf in the email sent to signers here.....', 'esig') . '">' . $custom_text . '</textarea>
						<label>Add formidable shortcode. IMPORTANT: (e.g [formidable-download form="1xmq8" dataset="formidable_entry_id" layout="1000" title="Download"] ) Keep formidable_entry_id which will be replaced dynamically after form submission process. It will also add Co-applicant PDFs.</label>
						';
			$more_contents .="<input type='text' id='esig_custom_message' name='esig_custom_message_shortcode_code' value='" . $shortcode . "' style='width:400px;'></div></p>";


			return $more_contents;
		}

		public function set_pdf_id( $entry_id, $form_id) {
			$docId = WP_E_Sig()->document->document_id_by_csum($document_checksum);
			//var_dump($docId);
//	        var_dump($entry_id);
//	        var_dump($form_id);
			$formidable_data = array(
				'entry_id' => $entry_id,
				'form_id'  => $form_id,
			);
			$this->formidable_var =  $formidable_data;
		}

		public function invite_custom_message($args, $document_checksum) {

			$docId = WP_E_Sig()->document->document_id_by_csum($document_checksum);
			//var_dump(WP_E_Sig()->document);die();
			$formidable_data = $this->formidable_var;
			//var_dump($formidable_data);
			$formidable_entry =  $formidable_data["entry_id"];
			$formidable_entry_id = $formidable_entry + 1;

			if ($this->isEnabled($docId)) {
			//echo "Please download PDF";
			//echo do_shortcode('[formidable-download form="1xmq8" dataset='.$formidable_entry_id.' layout="1000" title="Download"]');
				$shortcode_text =  $this->getCustomMessageText($docId) ;
				if( $shortcode_text ) {
					echo $shortcode_text;
				}
				$shortcode =  $this->getCustomMessageShortcode($docId) ;
				if( $shortcode ) {
					$short_code = str_replace("formidable_entry_id",$formidable_entry_id,$shortcode);
				}
				//$html.= $formidable_data['entry_id'];
				echo '<br/>';
				echo "Form PDF";
				echo do_shortcode($short_code);
				echo ' , ';
				echo "Co-applicant PDF";
				echo do_shortcode('[formidable-download form="1xmq8" dataset="'.$formidable_entry_id.'" layout="1001" title="Download"]');
			}
			//die();
		}

}

endif;