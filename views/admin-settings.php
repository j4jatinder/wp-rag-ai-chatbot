<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Admin Settings View for the WP RAG AI Chatbot Plugin.
 *
 * This file contains the HTML, CSS, and JavaScript for the settings page.
 * It is included by WP_RAG_AI_Chatbot_Admin::settings_page_html().
 */
?>
<div class="wrap">
    <h1><?php esc_html_e( 'AI Chatbot for Support & E-Commerce', 'ai-chatbot-for-support-ecommerce' ); ?></h1>

    <div id="rag-admin-message" class="notice" style="display:none;">
        <p></p>
    </div>

    <form method="post" action="options.php">
        <?php
        // Security fields and hidden settings fields
        settings_fields( 'wp-rag-ai-chatbot-group_static' );
        // The main section defined in settings_init()
        do_settings_sections( 'wp-rag-ai-chatbot-settings' );
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="wp_rag_ai_chatbot_chat_position"><?php esc_html_e( 'Chat Position', 'ai-chatbot-for-support-ecommerce' ); ?></label></th>
                <td>
                    <?php $chat_position = get_option( 'wp_rag_ai_chatbot_chat_position', 'bottom-right' ); ?>
                    <select name="wp_rag_ai_chatbot_chat_position" id="wp_rag_ai_chatbot_chat_position">
                        <option value="bottom-right" <?php selected( $chat_position, 'bottom-right' ); ?>>Bottom Right</option>
                        <option value="bottom-left" <?php selected( $chat_position, 'bottom-left' ); ?>>Bottom Left</option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Where the chatbot icon will appear on the frontend.', 'ai-chatbot-for-support-ecommerce' ); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button( __( 'Save Display Settings', 'ai-chatbot-for-support-ecommerce' ) ); ?>
    </form>

    <hr>

    <h2><?php esc_html_e( 'Site Registration Status', 'ai-chatbot-for-support-ecommerce' ); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e( 'Site ID', 'ai-chatbot-for-support-ecommerce' ); ?></th>
            <td>
                <code id="site-id-display"><?php echo esc_html( get_option( 'wp_rag_ai_chatbot_site_id', 'N/A' ) ); ?></code>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'API Key', 'ai-chatbot-for-support-ecommerce' ); ?></th>
            <td>
                <code id="api-key-display"><?php echo esc_html( get_option( 'wp_rag_ai_chatbot_api_key', 'N/A' ) ); ?></code>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Registration Status', 'ai-chatbot-for-support-ecommerce' ); ?></th>
            <td>
                <button id="site-register-button" class="button button-primary" <?php echo get_option( 'wp_rag_ai_chatbot_api_key' ) ? 'disabled' : ''; ?>>
                    <?php echo get_option( 'wp_rag_ai_chatbot_api_key' ) ? 'Site Registered' : '1. Register Site'; ?>
                </button>
                <p class="description"><?php esc_html_e( 'Registers this WordPress site with the RAG Node to get the Site ID and API Key.', 'ai-chatbot-for-support-ecommerce' ); ?></p>
            </td>
        </tr>
    </table>

    <hr>

    <h2><?php esc_html_e( 'AI Provider Configuration', 'ai-chatbot-for-support-ecommerce' ); ?></h2>
    <p class="description">Enter your AI keys and configure the active model. Keys are immediately sent to the RAG Node and **NOT** stored in this WordPress database.</p>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="active_provider"><?php esc_html_e( 'Active AI Provider', 'ai-chatbot-for-support-ecommerce' ); ?></label></th>
            <td>
                <?php $active_provider = get_option( 'wp_rag_ai_chatbot_active_provider', 'gemini' ); ?>
                <select id="active_provider">
                    <option value="gemini" <?php selected( $active_provider, 'gemini' ); ?>>Google Gemini</option>
                    <option value="openai" <?php selected( $active_provider, 'openai' ); ?>>OpenAI</option>
                </select>
                <p class="description"><?php esc_html_e( 'Select the primary provider the chatbot should use.', 'ai-chatbot-for-support-ecommerce' ); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="openai_key">OpenAI API Key</label></th>
            <td><input type="password" id="openai_key" class="regular-text" placeholder="sk-..." value="">
        <p class="hint">We are not storing theses keys into DB, its directly going to Chat Server.</p>
    <a href="https://platform.openai.com/api-keys"
		   target="_blank"
		   rel="noopener noreferrer">
			<?php esc_html_e( 'Get OpenAI API Key', 'ai-chatbot-for-support-ecommerce' ); ?>
		</a>
    </td>
        </tr>
        <tr>
            <th scope="row"><label for="openai_model">OpenAI Model</label></th>
            <td>
                <?php $openai_model = get_option( 'wp_rag_ai_chatbot_openai_model', 'gpt-5-nano' ); ?>
                <input type="text" id="openai_model" value="<?php echo esc_attr( $openai_model ); ?>" class="regular-text" placeholder="gpt-5-nano" />
                <p class="description"><?php esc_html_e( 'Specify the OpenAI model to use (e.g., gpt-5-nano or gpt-5-mini).', 'ai-chatbot-for-support-ecommerce' ); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="gemini_key">Gemini API Key</label></th>
            <td><input type="password" id="gemini_key" class="regular-text" placeholder="AIza..." value="">
        <p class="hint">We are not storing theses keys into DB, its directly going to Chat Server.</p>
    <a href="https://aistudio.google.com/app/apikey"
		   target="_blank"
		   rel="noopener noreferrer">
			<?php esc_html_e( 'Get Google Gemini API Key', 'ai-chatbot-for-support-ecommerce' ); ?>
		</a>
    </td>
        </tr>
        <tr>
            <th scope="row"><label for="gemini_model">Gemini Model</label></th>
            <td>
                <?php $gemini_model = get_option( 'wp_rag_ai_chatbot_gemini_model', 'gemini-2.5-flash-lite' ); ?>
                <input type="text" id="gemini_model" value="<?php echo esc_attr( $gemini_model ); ?>" class="regular-text" placeholder="gemini-2.5-flash-lite" />
                <p class="description"><?php esc_html_e( 'Specify the Gemini model to use (e.g., gemini-2.5-flash-lite).', 'ai-chatbot-for-support-ecommerce' ); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e( 'Key & Config Action', 'ai-chatbot-for-support-ecommerce' ); ?></th>
            <td>
                <?php $keys_sent_time = get_option( 'wp_rag_ai_chatbot_keys_sent' ); ?>
                <button id="send-keys-button" class="button button-secondary" <?php echo empty( get_option( 'wp_rag_ai_chatbot_api_key' ) ) ? 'disabled' : ''; ?>>
                    <?php esc_html_e( '2. Save AI Settings & Verify', 'ai-chatbot-for-support-ecommerce' ); ?>
                </button>
                <p class="description">
                    <?php
                    if ( $keys_sent_time ) {
                        echo 'Last successful config/key transmission: ' . esc_html( human_time_diff( $keys_sent_time ) ) . ' ago.';
                    } else {
                        esc_html_e( 'Keys and configuration have not been sent yet.', 'ai-chatbot-for-support-ecommerce' );
                    }
                    ?>
                </p>
            </td>
        </tr>
    </table>

    <hr>

    <h2><?php esc_html_e( 'Content Indexing (RAG)', 'ai-chatbot-for-support-ecommerce' ); ?></h2>
    <div class="form-control">
        <?php 
        foreach([ 'pages' => 'Pages(Selected Above)', 'faqs' => 'AI Chatbot FAQs','posts' => 'Posts(With tag "AI Chatbot Content")','products' => 'Products'] as $type => $label): ?>

            <div>
                <input type="checkbox" id="data_push_<?php echo esc_attr( $type ); ?>" name="wp_rag_ai_chatbot_data_push_types[]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, (array) get_option( 'wp_rag_ai_chatbot_data_push_types', [] ) ) ); ?> />
                <label for="data_push_<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></label>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="description"><?php esc_html_e( 'Push all selected WordPress content (Posts, Pages, Products, FAQs) to the RAG Node for indexing.', 'ai-chatbot-for-support-ecommerce' ); ?></p>
    <button id="data-push-button" class="button button-hero" <?php echo empty( get_option( 'wp_rag_ai_chatbot_api_key' ) ) ? 'disabled' : ''; ?>>
        3. Index Selected Content/Data Now
    </button>

</div>

<script>
jQuery(document).ready(function($) {
    var $msg = $('#rag-admin-message');
    var $msgP = $msg.find('p');

    // Function to display messages
    function displayMessage(type, message) {
        $msg.removeClass('notice-error notice-success').addClass('notice-' + type).show();
        $msgP.text(message);
        $('html, body').animate({ scrollTop: 0 }, 'slow');
        setTimeout(function() {
            $msg.fadeOut();
        }, 5000);
    }

    // --- 1. Site Registration Handler (Unchanged) ---
    $('#site-register-button').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var originalText = $btn.text();

        if ($('#wp_rag_ai_chatbot_node_url').val() === '') {
            displayMessage('error', 'Please enter and save the RAG Node URL first.');
            return;
        }

        $btn.prop('disabled', true).text('Registering...');

        $.ajax({
            url: wpRagChatbotAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_rag_ai_chatbot_register',
                security: wpRagChatbotAdmin.siteRegisterNonce,
            },
            success: function(response) {
                if (response.success) {
                    displayMessage('success', response.data.message);
                    $('#site-id-display').text(response.data.siteId);
                    $('#api-key-display').text(response.data.apiKey);
                    $btn.text('Site Registered').prop('disabled', true);
                    // Enable subsequent buttons
                    $('#send-keys-button, #data-push-button').prop('disabled', false);

                } else {
                    displayMessage('error', response.data.message);
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                displayMessage('error', 'An unknown error occurred during site registration.');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });

    // --- 2. Combined Config Save and Send Keys Handler (UPDATED) ---
    $('#send-keys-button').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var originalText = $btn.html(); // Use html() to preserve button tags
        var openaiKey = $('#openai_key').val();
        var geminiKey = $('#gemini_key').val();

        if ($('#api-key-display').text() === 'N/A') {
            displayMessage('error', 'Please complete Step 1: Register Site first.');
            return;
        }

        if (openaiKey === '' && geminiKey === '') {
            // Note: We allow sending an empty key if a previous key is already configured on the node
            // But we still want to save the configuration settings (models/provider)
            displayMessage('warning', 'No new API keys entered. Sending configuration update only.');
        }

        $btn.prop('disabled', true).text('Saving Config...');

        // Step A: Save Configuration Locally (Active Provider, Models)
        $.ajax({
            url: wpRagChatbotAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_rag_ai_chatbot_save_config',
                security: wpRagChatbotAdmin.saveConfigNonce,
                active_provider: $('#active_provider').val(),
                openai_model: $('#openai_model').val(),
                gemini_model: $('#gemini_model').val(),
            },
            success: function(response) {
                if (response.success) {
                    // Config saved locally, now proceed to send config and keys to RAG Node
                    $btn.text('Sending Keys...');
                    
                    // Step B: Send Keys & Saved Configuration to RAG Node
                    $.ajax({
                        url: wpRagChatbotAdmin.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wp_rag_ai_chatbot_send_api_keys',
                            security: wpRagChatbotAdmin.sendKeysNonce,
                            openai_key: openaiKey, 
                            gemini_key: geminiKey,
                        },
                        success: function(response) {
                            if (response.success) {
                                displayMessage('success', response.data.message);
                            } else {
                                displayMessage('error', 'Keys/Config Send Failed: ' + response.data.message);
                            }
                            $btn.html(originalText).prop('disabled', false);
                        },
                        error: function() {
                            displayMessage('error', 'An unknown error occurred while sending keys to the RAG Node.');
                            $btn.html(originalText).prop('disabled', false);
                        }
                    });

                } else {
                    displayMessage('error', 'Local Config Save Failed: ' + response.data.message);
                    $btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                displayMessage('error', 'An unknown error occurred during local config saving.');
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // --- 3. Data Push Handler (Unchanged) ---
    $('#data-push-button').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var originalText = $btn.text();
        var selectedDataTypes = $('input[name="wp_rag_ai_chatbot_data_push_types[]"]:checked').map(function() {
            return this.value;
        }).get();

        $btn.prop('disabled', true).text('Pushing Data... This may take up to 90 seconds.');

        $.ajax({
            url: wpRagChatbotAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_rag_ai_chatbot_push_data',
                security: wpRagChatbotAdmin.dataPushNonce,
                data_types: selectedDataTypes
            },
            success: function(response) {
                if (response.success) {
                    displayMessage('success', response.data.message);
                } else {
                    displayMessage('error', response.data.message);
                }
                $btn.text(originalText).prop('disabled', false);
            },
            error: function() {
                displayMessage('error', 'An unknown server error occurred during data push.');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });


$(document).on('click', '.wp-rag-ai-chatbot-domain-notice .notice-dismiss', function () {
    console.log("here");
    $.post(
        wpRagChatbotAdmin.ajaxurl,
        {
            action: 'wp_rag_ai_chatbot_dismiss_domain_notice',
            nonce: wpRagChatbotAdmin.noticeNonce
        }
    );
});

});


</script>
<?php
