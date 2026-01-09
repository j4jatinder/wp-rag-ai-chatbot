<?php
/*
Plugin Name: AI Chatbot for Support & E-Commerce
Plugin URI: https://www.phpsoftsolutions.in/ai-chatbot-for-support-e-commerce/
Description: An AI-powered chatbot for WordPress and WooCommerce using Retrieval-Augmented Generation (RAG). Train the chatbot on FAQs, pages, posts, and products, and answer customer queries using OpenAI or Gemini AI models.
Version: 1.0.0
Author: Jatinder Singh
Author URI: https://www.phpsoftsolutions.in
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ai-chatbot-for-support-e-commerce
*/


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$acsec_is_localhost = isset( $_SERVER['HTTP_HOST'] )
    ? in_array( wp_unslash($_SERVER['HTTP_HOST']), array( 'localhost','127.0.0.0.1', 'localhost:8000') )
    : false;

// Define plugin constants
define( 'ACSEC_VERSION', '1.0.0' );
define( 'ACSEC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACSEC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
// define('ACSEC_NODE_URL',$acsec_is_localhost ? 'http://backend_env:5000': 'https://ragai.phpsoftsolutions.in'); // Node.js server URL
define('ACSEC_NODE_URL', 'https://ragai.phpsoftsolutions.in'); // Node.js server URL

// Load the core classes
require_once ACSEC_PLUGIN_DIR . 'includes/class-acsec-chatbot-admin.php';
require_once ACSEC_PLUGIN_DIR . 'includes/class-acsec-chatbot-frontend.php';

// Include both parts
require_once plugin_dir_path(__FILE__) . 'includes/acsec-rest-endpoint.php';
require_once plugin_dir_path(__FILE__) . 'includes/acsec-frontend-chat.php';



/**
 * Initialize all classes.
 */
function acsec_chatbot_init() {
    ACSEC_Chatbot_Admin::instance();
}
add_action( 'plugins_loaded', 'acsec_chatbot_init' );
register_activation_hook( __FILE__, function () {
    // Register CPT before flushing
    ACSEC_Chatbot_Admin::instance()->register_faq_cpt();
    ACSEC_Chatbot_Admin::instance()->acsec_create_chatbot_tag();
    flush_rewrite_rules();
});
register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
});



?>