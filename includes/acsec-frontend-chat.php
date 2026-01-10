<?php
/**
 * ACSEC Chat - Frontend Embed
 * Registers shortcode [ACSEC_CHATBOT], enqueues React app, and provides REST endpoint + nonce to JS.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Plugin Frontend Shortcode for RAG Chatbot
 * Shortcode: [ACSEC_CHATBOT]
 */

add_shortcode('ACSEC_CHATBOT', 'acsec_chatbot_shortcode');

function acsec_chatbot_shortcode() {

    $acsec_chatbot_title= get_option( 'acsec_chatbot_chatbot_title', 'AI Chatbot' );

     $acsec_status_value = get_option( 'acsec_chatbot_current_status', '0' ); 
     if(!$acsec_status_value){
        return false;
     }
    $acsec_chat_position = get_option( 'acsec_chatbot_chat_position', 'right' );
    ob_start();
    ?>

    <!-- 🧠 Chatbot Popup Structure -->
    <div id="acsec-chatbot-wrapper" class="acsec-chatbot-<?php echo esc_attr($acsec_chat_position); ?>">
        <!-- Floating Button -->
        <button id="acsec-chatbot-button" class="acsec-chatbot-button">
            💬 <?php echo esc_html( $acsec_chatbot_title ); ?>
        </button>

        <!-- Modal -->
        <div id="acsec-chatbot-modal" class="acsec-chatbot-modal">
            <div class="acsec-chatbot-content">
                <button id="acsec-chatbot-close" class="acsec-chatbot-close">&times;</button>
                <div id="acsec-chatbot-app">
                    <!-- React app will render here -->
                     <?php acsec_render_acsec_chatbot_container(); ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}



function acsec_render_acsec_chatbot_container() {
        $acsec_chat_position = get_option( 'acsec_chatbot_chat_position', 'right' );

        // This div is the mount point for your React app
        echo '<div id="acsec-chatbot-root" class="position-' . esc_attr( $acsec_chat_position ) . '"></div>';
   
}   

function acsec_chatbot_render_in_footer() {
    echo do_shortcode('[ACSEC_CHATBOT]');
}
add_action( 'wp_footer', 'acsec_chatbot_render_in_footer'  );

// Set session cookie early, before headers are sent
add_action( 'wp_loaded', 'acsec_set_aichat_session_cookie', 1 );

function acsec_set_aichat_session_cookie() {
    $acsec_cookie_name = 'aichat_session_id';

    if (!isset($_COOKIE[$acsec_cookie_name])) {
        $acsec_user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $acsec_unique_id = wp_generate_password(10, false);
        $acsec_full_id = "session_{$acsec_user_id}_{$acsec_unique_id}";

        // Set expiration to the end of the current day (midnight)
        $acsec_expire = strtotime('tomorrow');

        setcookie($acsec_cookie_name, $acsec_full_id, $acsec_expire, COOKIEPATH, COOKIE_DOMAIN);
        $_COOKIE[$acsec_cookie_name] = $acsec_full_id; // Set for immediate use
    }
}
function acsec_get_aichat_session_id() {
    // First check if cookie exists
    if (isset($_COOKIE['aichat_session_id'])) {
        // Sanitize the cookie value before using it
        return sanitize_text_field(wp_unslash($_COOKIE['aichat_session_id']) );
    }
    
    // If no cookie, return empty - it will be set by wp_loaded hook
    return '';
}


/**
 * 2️⃣ Enqueue React app and expose REST endpoint & nonce
 */
add_action('wp_enqueue_scripts', function () {
    $acsec_plugin_dir = ACSEC_PLUGIN_DIR;
    $acsec_plugin_url = ACSEC_PLUGIN_URL;

    $acsec_main_js_file_name='chat-app-build/assets/index-BnRv7LeP.js';
    $acsec_main_css_file_name='chat-app-build/assets/index-BxZlaAnu.css';

    $acsec_react_js   = $acsec_plugin_dir . $acsec_main_js_file_name;
    $acsec_react_css  = $acsec_plugin_dir . $acsec_main_css_file_name;

    // Enqueue chatbot styles
    wp_enqueue_style(
        'acsec-chatbot-style',
        ACSEC_PLUGIN_URL . 'assets/css/chatbot-styles.css',
        array(),
        ACSEC_VERSION
    );

    // Enqueue CSS if exists
    if (file_exists($acsec_react_css)) {
        wp_enqueue_style(
            'acsec-chat-style',
            $acsec_plugin_url .  $acsec_main_css_file_name,
            [],
            filemtime($acsec_react_css)
        );
    }

    // Enqueue JS if exists
    if (file_exists($acsec_react_js)) {
        wp_enqueue_script(
            'acsec-chat-script',
            $acsec_plugin_url . $acsec_main_js_file_name,
            [],
            filemtime($acsec_react_js),
            true
        );

        // Add inline script for modal functionality
        $acsec_inline_script = "
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('acsec-chatbot-modal');
            const openBtn = document.getElementById('acsec-chatbot-button');
            const closeBtn = document.getElementById('acsec-chatbot-close');

            if (openBtn && modal && closeBtn) {
                openBtn.addEventListener('click', () => modal.style.display = 'block');
                closeBtn.addEventListener('click', () => modal.style.display = 'none');
                window.addEventListener('click', (e) => {
                    if (e.target === modal) modal.style.display = 'none';
                });
            }
        });
        ";
        wp_add_inline_script('acsec-chat-script', $acsec_inline_script);

        // Localize nonce & REST endpoint for React app
        wp_localize_script('acsec-chat-script', 'ACSEC_CHAT', [
            'wp_server_url'=>esc_url_raw(rest_url('acsec-chatbot/v1/')),
            'rest_send_url' => esc_url_raw(rest_url('acsec-chatbot/v1/api/chat/query')),
            'rest_fetch_url'=> esc_url_raw(rest_url('acsec-chatbot/v1/messages')),
            'nonce'    => wp_create_nonce('wp_rest'),
            'session_id' => acsec_get_aichat_session_id(),
            'chatbot_title' => get_option( 'acsec_chatbot_chatbot_title', 'AI Chatbot' ),
        ]);
    }
});


