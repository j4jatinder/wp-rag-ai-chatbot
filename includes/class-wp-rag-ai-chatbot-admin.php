<?php
/**
 * WP RAG AI Chatbot Admin Class
 *
 * Handles all admin-side functionality, including settings, AJAX handlers,
 * and content fetching for the RAG Node API.
 *
 * NOTE: This file assumes WP_RAG_AI_CHATBOT_PLUGIN_DIR is defined in the main plugin file.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define a constant path for demonstration if it's not defined elsewhere
if ( ! defined( 'WP_RAG_AI_CHATBOT_PLUGIN_DIR' ) ) {
    define( 'WP_RAG_AI_CHATBOT_PLUGIN_DIR', dirname( __FILE__ ) . '/' );
}

class WP_RAG_AI_Chatbot_Admin {
    /**
     * The single instance of the class.
     *
     * @var WP_RAG_AI_Chatbot_Admin
     */
    protected static $instance = null;

    /**
     * Main WP_RAG_AI_Chatbot_Admin Instance.
     *
     * Ensures only one instance of the admin class is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return WP_RAG_AI_Chatbot_Admin - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
       add_action( 'admin_notices', array($this, 'wp_rag_ai_chatbot_show_domain_notice') );
       add_action(
    'wp_ajax_wp_rag_ai_chatbot_dismiss_domain_notice',
    array( $this, 'dismiss_domain_notice' )
);


        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        // In your plugin's main file or the class constructor
        add_action( 'init', array( $this, 'register_faq_cpt' ), 0 );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) ); // Hooked here

        // AJAX Handlers
        add_action( 'wp_ajax_wp_rag_ai_chatbot_register', array( $this, 'handle_site_register_ajax' ) );
        add_action( 'wp_ajax_wp_rag_ai_chatbot_push_data', array( $this, 'handle_data_push_ajax' ) );
        add_action( 'wp_ajax_wp_rag_ai_chatbot_send_api_keys', array( $this, 'handle_api_keys_ajax' ) );
        // --- NEW HANDLER for saving configuration locally ---
        add_action( 'wp_ajax_wp_rag_ai_chatbot_save_config', array( $this, 'handle_ai_config_save' ) );
        add_action( 'wp_ajax_rag_search_pages', array( $this, 'rag_search_pages_ajax' ) );
    }

    /**
     * Enqueue necessary scripts and localize data for AJAX.
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
      
        $screen = get_current_screen();
        if ( empty( $screen ) || $screen->id !== 'toplevel_page_wp-rag-ai-chatbot-settings' ) {
            return;
        }


        wp_enqueue_script('jquery');
        
        // Localize data: This step DEFINES the global JavaScript object 'wpRagChatbotAdmin'
        wp_localize_script( 'jquery', 'wpRagChatbotAdmin', array(
            'ajaxurl'            => admin_url( 'admin-ajax.php' ),
            'dataPushNonce'      => wp_create_nonce( 'wp-rag-ai-chatbot-data-push' ),
            'siteRegisterNonce'  => wp_create_nonce( 'wp-rag-ai-chatbot-site-register' ),
            'sendKeysNonce'      => wp_create_nonce( 'wp-rag-ai-chatbot-send-keys' ),
            'saveConfigNonce'    => wp_create_nonce( 'wp-rag-ai-chatbot-save-config' ), // New nonce
            'noticeNonce'    => wp_create_nonce( 'wp_rag_ai_chatbot_notice_nonce' ),
        ) );

        // Check if we are on the correct settings page before enque
        wp_enqueue_script( 'rag-page-selector', plugin_dir_url( __FILE__ ) . '../assets/js/rag-page-selector.js', array( 'jquery' ), '1.0', true );

        // Pass data to the JavaScript file
        wp_localize_script( 'rag-page-selector', 'ragAdmin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'rag-page-search-nonce' ),
            'i18n'     => array(
                'no_pages_selected' => __( 'No pages selected yet.', 'ai-chatbot-for-support-ecommerce' ),
                'no_results'        => __( 'No results found.', 'ai-chatbot-for-support-ecommerce' ),
            ),
        ) );
    // Now, call your CSS function, which should be renamed/refactored
    $this->enqueue_page_selector_styles();

    }
// Inside your main plugin class

public function add_admin_menu() {
    // Add top-level menu item (Settings Page)
    add_menu_page(
        __( 'AI Chatbot Settings', 'ai-chatbot-for-support-ecommerce' ),
        __( 'AI Chatbot', 'ai-chatbot-for-support-ecommerce' ),
        'manage_options',
        'wp-rag-ai-chatbot-settings', // The parent slug
        array( $this, 'settings_page_html' ),
        'dashicons-format-chat',
        6
    );

    // Add the Custom Post Type (FAQ) management link as a submenu
    // We use edit.php?post_type=YOUR_SLUG as the menu slug.
    add_submenu_page(
        'wp-rag-ai-chatbot-settings', // Parent slug: MUST match the slug of add_menu_page above
        __( 'AI Chatbot FAQs (RAG)', 'ai-chatbot-for-support-ecommerce' ), // Page title
        __( 'AI Chatbot FAQs (RAG)', 'ai-chatbot-for-support-ecommerce' ), // Menu title
        'manage_options',
        'edit.php?post_type=rag_ai_chatbot_faq' // Target link for the CPT list screen
    );
    
    // Optional: Add a "Add New FAQ" link directly under the menu as well
    add_submenu_page(
        'wp-rag-ai-chatbot-settings',
        __( 'Add New AI Chatbot FAQ', 'ai-chatbot-for-support-ecommerce' ),
        __( 'Add New AI Chatbot FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'manage_options',
        'post-new.php?post_type=rag_ai_chatbot_faq'
    );
}
    // Inside your main plugin class

/**
 * Register the 'rag_ai_chatbot_faq' Custom Post Type.
 * This CPT is used to feed question/answer pairs to the RAG model.
 */
public function register_faq_cpt() {
    $labels = array(
        'name'                  => _x( 'AI Chatbot FAQs (RAG)', 'Post Type General Name', 'ai-chatbot-for-support-ecommerce' ),
        'singular_name'         => _x( 'AI Chatbot FAQs (RAG)', 'Post Type Singular Name', 'ai-chatbot-for-support-ecommerce' ),
        'menu_name'             => __( 'AI Chatbot FAQs (RAG)', 'ai-chatbot-for-support-ecommerce' ),
        'name_admin_bar'        => __( 'RAG FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'archives'              => __( 'FAQ Archives', 'ai-chatbot-for-support-ecommerce' ),
        'attributes'            => __( 'FAQ Attributes', 'ai-chatbot-for-support-ecommerce' ),
        'parent_item_colon'     => __( 'Parent FAQ:', 'ai-chatbot-for-support-ecommerce' ),
        'all_items'             => __( 'All AI Chatbot FAQs (RAG)', 'ai-chatbot-for-support-ecommerce' ),
        'add_new_item'          => __( 'Add New RAG AI FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'add_new'               => __( 'Add New', 'ai-chatbot-for-support-ecommerce' ),
        'new_item'              => __( 'New AI Chatbot FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'edit_item'             => __( 'Edit AI Chatbot FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'update_item'           => __( 'Update AI Chatbot FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'view_item'             => __( 'View AI FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'view_items'            => __( 'View AI FAQs', 'ai-chatbot-for-support-ecommerce' ),
        'search_items'          => __( 'Search AI FAQs', 'ai-chatbot-for-support-ecommerce' ),
        'not_found'             => __( 'Not found', 'ai-chatbot-for-support-ecommerce' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'ai-chatbot-for-support-ecommerce' ),
        'featured_image'        => __( 'Featured Image', 'ai-chatbot-for-support-ecommerce' ),
        'set_featured_image'    => __( 'Set featured image', 'ai-chatbot-for-support-ecommerce' ),
        'remove_featured_image' => __( 'Remove featured image', 'ai-chatbot-for-support-ecommerce' ),
        'use_featured_image'    => __( 'Use as featured image', 'ai-chatbot-for-support-ecommerce' ),
        'insert_into_item'      => __( 'Insert into FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'uploaded_to_this_item' => __( 'Uploaded to this FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'items_list'            => __( 'FAQs list', 'ai-chatbot-for-support-ecommerce' ),
        'items_list_navigation' => __( 'FAQs list navigation', 'ai-chatbot-for-support-ecommerce' ),
        'filter_items_list'     => __( 'Filter FAQs list', 'ai-chatbot-for-support-ecommerce' ),
    );
    $args = array(
        'label'                 => __( 'RAG AI FAQ', 'ai-chatbot-for-support-ecommerce' ),
        'description'           => __( 'Questions and answers to feed to the RAG AI model.', 'ai-chatbot-for-support-ecommerce' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor' ), // Title is 'question', Editor is 'answer'
        'hierarchical'          => false,
        'public'                => true, // Can be set to false if only used for AI
        'show_ui'               => true,
        'show_in_menu'          => false, // <-- Crucial: We will manually add it as a submenu
        'menu_position'         => 5,
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => true, // Typically excluded from front-end search
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'rewrite'               => false, //array( 'slug' => 'rag-ai-chatbot-faq' ), // Friendly URL slug
        'show_in_rest'          => true, // Enable for Gutenberg/REST API
    );
    register_post_type( 'rag_ai_chatbot_faq', $args );
}

// Ensure you hook this up in your class constructor or initialization:
// add_action( 'init', array( $this, 'register_faq_cpt' ) );

    public function settings_init() {
        // Register connection settings

        // Define a settings section
        add_settings_section(
            'wp_rag_ai_chatbot_main_section',
            __( 'AI Chatbot Display & Behavior', 'ai-chatbot-for-support-ecommerce' ),
            array( $this, 'main_settings_section_callback' ),
            'wp-rag-ai-chatbot-settings'
        );

        // register_setting( 'wp-rag-ai-chatbot-group', 'wp_rag_ai_chatbot_node_url' );
        register_setting( 'wp-rag-ai-chatbot-group', 'wp_rag_ai_chatbot_site_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'wp-rag-ai-chatbot-group', 'wp_rag_ai_chatbot_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'wp-rag-ai-chatbot-group', 'wp_rag_ai_chatbot_keys_sent' , array(
        'sanitize_callback' => 'absint',
    )); // Timestamp of last key send
        register_setting( 'wp-rag-ai-chatbot-group', 'wp_rag_ai_chatbot_data_push_types',    array(
        'sanitize_callback' => array( $this, 'sanitize_data_push_types' ),
    ) ); // Timestamp of last key send

        // Register general settings
        
        register_setting( 'wp-rag-ai-chatbot-group_static', 'wp_rag_ai_chatbot_chat_position',  array(
        'sanitize_callback' => array( $this, 'sanitize_chat_position' ),
    ) );

        register_setting( 'wp-rag-ai-chatbot-group_static', 'wp_rag_ai_chatbot_chatbot_title',  array(
        'sanitize_callback' => 'sanitize_text_field',
    ) );
        // 1. Register the new policy pages setting
        register_setting(
        'wp-rag-ai-chatbot-group_static', // Option group
        'wp_rag_ai_chatbot_policy_pages', // Option name
        array( // <-- Correct top-level array for the schema
            'type'              => 'array',
            'description'       => 'List of IDs for pages the RAG AI should use as policy sources.',
           'sanitize_callback' => array( $this, 'wp_rag_ai_chatbot_sanitize_page_ids' ), // Correct callback syntax for class method
            'default'           => array(),
            'show_in_rest'      => false,
        )
    );
    register_setting(
        'wp-rag-ai-chatbot-group_static', // Option group
        'wp_rag_ai_chatbot_current_status', // Option name
         array(
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default'           => false,
         ),
        array( // <-- Correct top-level array for the schema
            'type'              => 'boolean',
            'description'       => 'Enable/Disable on frontend?',
           // 'sanitize_callback' => array( $this, 'wp_rag_ai_chatbot_sanitize_page_ids' ), // Correct callback syntax for class method
            'default'           => 0,
            'show_in_rest'      => false,
        )
    );


        // --- Register AI Configuration Settings (No longer added via form, but still need to be registered) ---
        register_setting( 'wp-rag-ai-chatbot-group', 'wp_rag_ai_chatbot_active_provider',   array(
        'sanitize_callback' => array( $this, 'sanitize_active_provider' ),
    ) );
        register_setting( 'wp-rag-ai-chatbot-group', 'wp_rag_ai_chatbot_openai_model',   array(
        'sanitize_callback' => 'sanitize_text_field',
    ) );
        register_setting( 'wp-rag-ai-chatbot-group', 'wp_rag_ai_chatbot_gemini_model',   array(
        'sanitize_callback' => 'sanitize_text_field',
    ) );
        

      

        add_settings_field(
            'wp_rag_ai_chatbot_current_status_field',
            __( 'Enable/Disable on Frontend', 'ai-chatbot-for-support-ecommerce' ),
            array( $this, 'wp_rag_ai_switch_status_callback' ),
            'wp-rag-ai-chatbot-settings',
            'wp_rag_ai_chatbot_main_section',
             array(
            'label_for' => 'wp_rag_ai_chatbot_current_status_field', // Associates the title label with the input field
        )
        );
        add_settings_field(
            'wp_rag_ai_chatbot_policy_pages_field',
            __( 'Pages for RAG', 'ai-chatbot-for-support-ecommerce' ),
            array( $this, 'wp_rag_ai_chatbot_policy_pages_callback' ),
            'wp-rag-ai-chatbot-settings',
            'wp_rag_ai_chatbot_main_section'

        );
         add_settings_field(
            'wp_rag_ai_chatbot_chatbot_title_field',
            __( 'Chatbot Widget Title', 'ai-chatbot-for-support-ecommerce' ),
            array( $this, 'wp_rag_ai_chatbot_chatbot_title_callback' ),
            'wp-rag-ai-chatbot-settings',
            'wp_rag_ai_chatbot_main_section'

        );

        
    }

    public function sanitize_chat_position( $value ) {
    $allowed = array( 'left', 'right' );
    return in_array( $value, $allowed, true ) ? $value : 'right';
}

public function sanitize_active_provider( $value ) {
    $allowed = array( 'gemini', 'openai' );
    return in_array( $value, $allowed, true ) ? $value : 'gemini';
}


public function sanitize_data_push_types( $input ) {
    if ( ! is_array( $input ) ) {
        return array();
    }

    return array_map( 'sanitize_text_field', $input );
}

// Field Callback (Outputs the switch/checkbox HTML)
function wp_rag_ai_switch_status_callback() {
    $option_value = get_option( 'wp_rag_ai_chatbot_current_status', '0' ); // Get the current value, default to '0' (off)
// var_dump($option_value);
    echo '<label for="wp_rag_ai_chatbot_current_status">';
    echo '<input type="checkbox" id="wp_rag_ai_chatbot_current_status" name="wp_rag_ai_chatbot_current_status" value="1" ' . checked( '1', $option_value, false ) . '/>';
    echo ' Check to enable the feature.</label>'; // Add descriptive text next to the checkbox
}
    // Inside your main plugin class

/**
 * Handles the AJAX request to search for pages.
 */
public function rag_search_pages_ajax() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.' );
    }


    if (
    ! isset( $_POST['_wpnonce'] ) ||
    ! wp_verify_nonce(
        sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ),
        'rag-page-search-nonce'
    )
) {
    wp_send_json_error( 'Invalid nonce' );
}



    $search_term = sanitize_text_field( wp_unslash( $_POST['s'] ?? '' ) );

    if ( empty( $search_term ) ) {
        wp_send_json_success( array() ); // Return empty results if search term is empty
    }

    // Query WordPress for pages matching the search term
    $pages_query = new WP_Query( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        's'              => $search_term, // Use the search term
        'posts_per_page' => 10,          // Limit results to prevent bloat
        'fields'         => 'id=>title', // Only fetch ID and Title
    ) );

    $results = array();
    if ( $pages_query->have_posts() ) {
        foreach ( $pages_query->posts as $page ) {
            $results[] = array(
                'id'    => $page->ID,
                'title' => get_the_title( $page->ID ),
            );
        }
    }

    wp_send_json_success( $results );
}


 // Inside your main plugin class
// Inside your main plugin class

public function wp_rag_ai_chatbot_policy_pages_callback() {
    // Get the currently saved array of page IDs
    $selected_page_ids = get_option( 'wp_rag_ai_chatbot_policy_pages', array() );
    ?>
    <div id="rag-policy-pages-selector">
        
        <input type="text" id="rag-page-search" placeholder="<?php esc_attr_e( 'Type to search for a page...', 'ai-chatbot-for-support-ecommerce' ); ?>" style="width: 100%; max-width: 400px;" />
        
        <ul id="rag-page-search-results" style="border: 1px solid #ccc; max-height: 150px; overflow-y: auto; list-style: none; padding: 5px; margin-top: 5px; background: #fff; display: none;">
            <li class="loading" style="display: none; padding: 5px;"><?php esc_html_e( 'Searching...', 'ai-chatbot-for-support-ecommerce' ); ?></li>
        </ul>

        <p class="description"><?php esc_html_e( 'Selected pages will be indexed for AI responses.
Maximum 100 pages. Content is truncated to 1000 words per page.', 'ai-chatbot-for-support-ecommerce' ); ?></p>

        <div id="rag-selected-policy-pages">
            <h4><?php esc_html_e( 'Currently Selected Pages:', 'ai-chatbot-for-support-ecommerce' ); ?></h4>
            <ul id="rag-selected-pages-list" class="rag-token-list" style="list-style: none; padding: 0;">
                <?php
                if ( ! empty( $selected_page_ids ) ) {
                    foreach ( $selected_page_ids as $page_id ) {
                        $page_title = get_the_title( $page_id );
                        if ( $page_title ) {
                            ?>
                            <li data-page-id="<?php echo esc_attr( $page_id ); ?>">
                                <?php echo esc_html( $page_title ); ?> 
                                <input type="hidden" name="wp_rag_ai_chatbot_policy_pages[]" value="<?php echo esc_attr( $page_id ); ?>" />
                                
                                <button type="button" class="rag-remove-page rag-icon-remove" data-page-id="<?php echo esc_attr( $page_id ); ?>" title="<?php esc_attr_e( 'Remove Page', 'ai-chatbot-for-support-ecommerce' ); ?>">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                            </li>
                            <?php
                        }
                    }
                } else {
                    echo '<li id="rag-no-pages-selected" class="no-pages-placeholder">No pages selected yet.</li>';
                }
                ?>
            </ul>
        </div>
    </div>
    <?php
    $this->enqueue_page_selector_styles(); // Call the method to inject the styles
}
// Inside your main plugin class

// Inside your main plugin class

public function enqueue_page_selector_styles() {
    wp_enqueue_style( 'dashicons' );
    
    $css = '
        /* --- CLUSTER/TOKEN CONTAINER --- */
        #rag-selected-pages-list.rag-token-list {
            display: flex; /* Key to clustering */
            flex-wrap: wrap; /* Key to wrapping to the next line */
            gap: 8px; /* Spacing between tokens (horizontal and vertical) */
            margin-top: 10px;
        }
        
        /* --- INDIVIDUAL TOKEN STYLING --- */
        #rag-selected-pages-list.rag-token-list > li {
            background: #e3e3e3;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 4px 8px 4px 10px; 
            line-height: 1;
            display: flex;
            align-items: center;
            font-size: 13px;
            
            /* Ensure text doesn\'t wrap inside the token if possible */
            white-space: nowrap; 
        }
        
        /* --- REMOVE ICON STYLING (Kept for completeness) --- */
        .rag-icon-remove {
            background: none;
            border: none;
            padding: 0 0 0 5px;
            cursor: pointer;
            color: #a00; 
            height: 16px;
            width: 16px;
            margin-left: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.1s ease;
        }
        .rag-icon-remove:hover {
            color: #d00;
        }
        .rag-icon-remove .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
            line-height: 1;
        }
        .rag-token-list .no-pages-placeholder {
            padding: 4px 0;
            background: none;
            border: none;
            font-style: italic;
        }
    ';
    wp_add_inline_style( 'wp-admin', $css );
}

// 3. Sanitization Method
public function wp_rag_ai_chatbot_sanitize_page_ids( $input ) {
    if ( is_array( $input ) ) {
        $sanitized_ids = array_map( 'intval', $input );
        // Filter out any zero/invalid values
        return array_filter( $sanitized_ids );
    }
    return array();
}
public function wp_rag_ai_chatbot_show_domain_notice() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( empty( $screen ) || $screen->id !== 'toplevel_page_wp-rag-ai-chatbot-settings' ) {
        return;
    }

    // Optional: show only on localhost
    $host = wp_parse_url( home_url(), PHP_URL_HOST );
    if ( ! in_array( $host, array( 'localhost', '127.0.0.1' ), true ) ) {
        return;
    }

    // Check dismissal
    if ( get_user_meta( get_current_user_id(), 'wp_rag_ai_chatbot_domain_notice_dismissed', true ) ) {
        return;
    }

    echo '<div class="notice notice-info is-dismissible wp-rag-ai-chatbot-domain-notice">';
    echo '<p><strong>' . esc_html__( 'AI Chatbot', 'ai-chatbot-for-support-ecommerce' ) . '</strong></p>';
    echo '<p>' . esc_html__(
        'This plugin requires a publicly accessible HTTPS domain. Localhost and local development environments are not supported.',
        'ai-chatbot-for-support-ecommerce'
    ) . '</p>';
    echo '</div>';
}
public function dismiss_domain_notice() {

    check_ajax_referer( 'wp_rag_ai_chatbot_notice_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error();
    }

    update_user_meta(
        get_current_user_id(),
        'wp_rag_ai_chatbot_domain_notice_dismissed',
        1
    );

    wp_send_json_success();
}

/**
 * Creates the "AI Chatbot Content" tag if it does not already exist.
 */
public function wp_rag_ai_create_chatbot_tag() {
    $tag_name = 'AI Chatbot Content';
    $taxonomy = 'post_tag';

    // 1. Check if the term already exists to avoid duplicates
    // Since WP 6.0, term_exists results are cached for better performance.
    if ( ! term_exists( $tag_name, $taxonomy ) ) {
        
        // 2. Insert the term into the post_tag taxonomy
        wp_insert_term(
            $tag_name, 
            $taxonomy,
            array(
                'description' => 'Posts to train AI Chatbot responses.',
                'slug'        => 'ai-chatbot-content'
            )
        );
    }
}


    public function main_settings_section_callback() {
      //  echo '<p class="notice notice-info">' . esc_html__( 'Note: Note: This plugin requires a publicly accessible HTTPS domain. Localhost and local development environments are not supported.' ) . '</p>';
    }

    public function wp_rag_ai_chatbot_chatbot_title_callback() {
        $chatbot_title = get_option( 'wp_rag_ai_chatbot_chatbot_title', 'AI Chatbot' );
        echo '<input type="text" id="wp_rag_ai_chatbot_chatbot_title" name="wp_rag_ai_chatbot_chatbot_title" value="' . esc_attr( $chatbot_title ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'The title displayed on the chatbot widget header.', 'ai-chatbot-for-support-ecommerce' ) . '</p>';
    }
    public function node_url_field_callback() {
        $node_url = get_option( 'wp_rag_ai_chatbot_node_url', 'https://your-rag-node.com/' );
        echo '<input type="url" id="wp_rag_ai_chatbot_node_url" name="wp_rag_ai_chatbot_node_url" value="' . esc_attr( $node_url ) . '" class="regular-text" required />';
        echo '<p class="description">' . esc_html__( 'The base URL of your RAG AI Chatbot Node Server.', 'ai-chatbot-for-support-ecommerce' ) . '</p>';
    }

    public function settings_page_html() {
        // Load the HTML for the admin page
        $path = WP_RAG_AI_CHATBOT_PLUGIN_DIR . 'views/admin-settings.php';
        if ( file_exists( $path ) ) {
            include $path;
        } else {
            echo '<div class="notice notice-error"><p><strong>Error:</strong> Admin settings view file not found at ' . esc_html( $path ) . '</p></div>';
        }
    }

    // --- NEW HANDLER: Saves AI Model/Provider configuration locally via AJAX ---
    public function handle_ai_config_save() {
        check_ajax_referer( 'wp-rag-ai-chatbot-save-config', 'security' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }


        $active_provider = isset( $_POST['active_provider'] )
    ? sanitize_text_field( wp_unslash( $_POST['active_provider'] ) )
    : 'gemini';

$openai_model = isset( $_POST['openai_model'] )
    ? sanitize_text_field( wp_unslash( $_POST['openai_model'] ) )
    : 'gpt-4-turbo';

$gemini_model = isset( $_POST['gemini_model'] )
    ? sanitize_text_field( wp_unslash( $_POST['gemini_model'] ) )
    : 'gemini-2.5-flash';


        // Save the non-key configuration settings
        update_option( 'wp_rag_ai_chatbot_active_provider', $active_provider );
        update_option( 'wp_rag_ai_chatbot_openai_model', $openai_model );
        update_option( 'wp_rag_ai_chatbot_gemini_model', $gemini_model );

        wp_send_json_success( array( 'message' => 'AI configuration saved locally.' ) );
    }
    
    // --- API HANDLERS (handle_api_keys_ajax remains similar, fetching saved config) ---
/**
     * Handles the AJAX request to register the site with the Node server using
     * the Challenge-Response verification flow.
     */
    public function handle_site_register_ajax() {
        check_ajax_referer( 'wp-rag-ai-chatbot-site-register', 'security' );
        // ... (unchanged logic)
        $node_url = WP_RAG_AI_CHATBOT_NODE_URL;
        if ( empty( $node_url ) ) {
            wp_send_json_error( array( 'message' => 'RAG Node URL is required. Please save it first.' ) );
        }

        
        // --- Gather site and admin information ---
        $site_name   = get_bloginfo( 'name' );
        $site_url    = get_site_url();
        $admin_email = get_option( 'admin_email' );
        $admin_name  = 'WordPress Admin'; // Default fallback name

        $admin_user = get_user_by( 'email', $admin_email );
        if ( $admin_user ) {
            $admin_name = $admin_user->display_name;
        }


        // Step 1: Get token
    $token_response = wp_remote_post( "$node_url/api/site/request-token", [
        'method'  => 'POST',
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode(['siteUrl' => $site_url]),
    ]);
    $token_data = json_decode( wp_remote_retrieve_body( $token_response ), true );
    $token = $token_data['token'] ?? null;

    if ( ! $token ) {
        wp_send_json_error(['message' => 'Failed to get token from Node server.',$token_response]);
    }

        // Store the token in a temporary option for the public REST endpoint to serve
        update_option( 'wp_rag_ai_chatbot_challenge_token_temp', $token, false );


        // ----------------------------------------------------------------------
        // Step 2: Finalize Registration by sending the public verification URL
        // ----------------------------------------------------------------------
        // The public URL the Node server will call to verify ownership
    // $challenge_verification_url = 'http://lamp-web-1:8000/wooc/index.php?rest_route=/wp-rag-ai-chatbot/v1/challenge-token';// get_rest_url( null, 'wp-rag-ai-chatbot/v1/challenge-token' );
    $challenge_verification_url = get_rest_url( null, 'wp-rag-ai-chatbot/v1/challenge-token' );

    // Result will be something like:
    // 'https://yourwordpress.com/wp-json/wp-rag-ai-chatbot/v1/challenge-token'

        $payload = [
            'siteName'   => $site_name,
            'siteUrl'    => $site_url,
            'ownerEmail' => $admin_email,
            'ownerName'  => $admin_name,
            'token'      => $token,
            'challengeVerificationUrl' => $challenge_verification_url, // <-- New key
        ];

        $response = wp_remote_post( "$node_url/api/site/register", [
            'method'  => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode( $payload ),
            'timeout' => 30, // Increased timeout for external fetch on Node side
        ]);

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'API Error during registration: ' . $response->get_error_message() ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        
        // ----------------------------------------------------------------------
        // Step 3: Cleanup and Final Key Storage
        // ----------------------------------------------------------------------

        // Always delete the temporary challenge token immediately after the attempt
        delete_option( 'wp_rag_ai_chatbot_challenge_token_temp' );



        if ( 200 === $code && ! empty( $body['siteId'] ) && ! empty( $body['apiKey'] ) ) {
            update_option( 'wp_rag_ai_chatbot_site_id', $body['siteId'] );
            update_option( 'wp_rag_ai_chatbot_api_key', $body['apiKey'] );

            wp_send_json_success( array(
                'message' => 'Site registered successfully!',
                'siteId'  => $body['siteId'],
                'apiKey'  => $body['apiKey'],
            ) );
        } else {
            $error_message = isset( $body['message'] ) ? $body['message'] : 'Registration failed with status code: ' . $code;
            wp_send_json_error( array( 'message' => $error_message ) );
        }

        

        
    }

    /**
     * (B) Handles sending user-provided AI keys (e.g., OpenAI, Gemini) and configuration to the Node.
     */
    public function handle_api_keys_ajax() {
        check_ajax_referer( 'wp-rag-ai-chatbot-send-keys', 'security' );

        $node_url = WP_RAG_AI_CHATBOT_NODE_URL;
        $site_id  = get_option( 'wp_rag_ai_chatbot_site_id' );
        $api_key  = get_option( 'wp_rag_ai_chatbot_api_key' ); // Site API Key

        if ( empty( $node_url ) || empty( $site_id ) || empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => 'Site Registration is incomplete. Cannot send AI keys.' ) );
        }

        // --- Retrieve AI Keys from POST Data (sent via JavaScript) ---

        $openai_key_default = isset( $_POST['openai_key'] ) ? sanitize_text_field( wp_unslash( $_POST['openai_key'] ) ) : ''    ;
        $gemini_key_default= isset( $_POST['gemini_key'] ) ? sanitize_text_field( wp_unslash( $_POST['gemini_key'] ) ) : ''    ;
        $ai_keys = array(
            'OPENAI_API_KEY' => $openai_key_default,
            'GEMINI_API_KEY' => $gemini_key_default,
        );

        // --- Retrieve AI Configuration from Saved Options (saved by handle_ai_config_save) ---
        $active_provider = get_option( 'wp_rag_ai_chatbot_active_provider', 'gemini' );
        $openai_model    = get_option( 'wp_rag_ai_chatbot_openai_model', 'gpt-4-turbo' );
        $gemini_model    = get_option( 'wp_rag_ai_chatbot_gemini_model', 'gemini-2.5-flash-preview-09-2025' );

        // Payload for the RAG Node
        $payload = array(
            'siteId' => $site_id,
            'keys'   => $ai_keys,
            'config' => [
                'activeProvider' => $active_provider,
                'openaiModel'    => $openai_model,
                'geminiModel'    => $gemini_model,
            ]
        );

        $api_endpoint = trailingslashit( $node_url ) . 'api/site/configure-ai-keys';

        $response = wp_remote_post( $api_endpoint, array(
            'method'    => 'POST',
            'headers'   => array(
                'Content-Type'  => 'application/json',
                'x-site-key'    => $api_key,
            ),
            'body'      => json_encode( $payload ),
            'timeout'   => 30,
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            $message = is_wp_error( $response ) ? $response->get_error_message() : 'Failed to send keys. Check Node logs.';
            wp_send_json_error( array( 'message' => 'Error sending AI keys: ' . $message , 'payload'=>$payload ) );
        }

        // Keys successfully sent, record timestamp
        update_option( 'wp_rag_ai_chatbot_keys_sent', time() );
        wp_send_json_success( array( 'message' => 'AI keys and configuration sent successfully.' ) );
    }

    /**
     * (C) Data Push Logic - The Universal Content Handler
     * ... (unchanged)
     */
    public function handle_data_push_ajax() {
        check_ajax_referer( 'wp-rag-ai-chatbot-data-push', 'security' );
        // ... (unchanged logic)
        $node_url = WP_RAG_AI_CHATBOT_NODE_URL;
        $api_key  = get_option( 'wp_rag_ai_chatbot_api_key' );

        if ( empty( $node_url ) || empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => 'Please complete Site Registration first.' ) );
        }

$posted_data_types = isset( $_POST['data_types'] )
    ? sanitize_text_field( wp_unslash( $_POST['data_types'] ) )
    : [];



        if(empty($posted_data_types)){
            wp_send_json_error( array( 'message' => 'Please Select Data Synchronous types!' ) );
        }
        update_option( 'wp_rag_ai_chatbot_data_push_types', $posted_data_types );// Save selected data types
        
        
        // --- 1. Gather ALL Universal WordPress Data ---
        $data_to_send = [
            'faqs'     => [],
            'policies' => [],
            'posts' => [],
            'products' => [],
        ];

        if(in_array('faqs', $posted_data_types)){
            $data_to_send['faqs']=$this->fetch_faqs_cpt( 'rag_ai_chatbot_faq' );
            // echo "A";
        }
        if(in_array('pages', $posted_data_types)){
            $data_to_send['pages']=$this->fetch_wordpress_pages_non_policy();
            // echo "B";
        }
        if(in_array('posts',$posted_data_types) ){
            
            $data_to_send['posts']=$this->fetch_rag_ai_content_posts();
            // echo "C";
        }
        if(in_array('policies',$posted_data_types)){
            $data_to_send['policies']=$this->fetch_rag_ai_content_policy_pages();
            // echo "D";
        }
        // --- 2. Conditional WooCommerce Data ---
        if ( function_exists( 'WC' )  && in_array('products',$posted_data_types) ) {
            $data_to_send['products'] = $this->fetch_woocommerce_products();
        }
        // --- 3. Prepare and Send the HTTP Request ---
        $api_endpoint = trailingslashit( $node_url ) . 'api/site/push-data';

        $response = wp_remote_post( $api_endpoint, array(
            'method'    => 'POST',
            'headers'   => array(
                'Content-Type'  => 'application/json',
                'x-site-key'    => $api_key, // REQUIRED Header
            ),
            'body'      => json_encode( $data_to_send ),
            'timeout'   => 90, // Increased timeout for large data sets
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'Data Push API Error: ' . $response->get_error_message() ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( 200 === $code ) {
            $success_message = isset( $body['message'] ) ? $body['message'] : 'Content successfully pushed to the RAG Node.';
            wp_send_json_success( array( 'message' => $success_message ) );
        } else {
            $error_message = isset( $body['message'] ) ? $body['message'] : 'Data push failed with status code: ' . $code.json_encode($data_to_send);
            wp_send_json_error( array( 'message' => $error_message ) );
        }
    }
    
 
    // --- CONTENT FETCHING HELPERS ---

    /**
     * (D) Fetch standard published posts.
     */
    private function fetch_wordpress_posts() {
        $posts_data = [];
        $posts = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'fields'         => 'ids', // Optimization: fetch only IDs first
        ) );

        foreach ( $posts as $post_id ) {
            $post = get_post( $post_id );
            $posts_data[] = [
                'id'       => $post->ID,
                'type'     => $post->post_type,
                'title'    => $post->post_title,
                'content'  => wp_strip_all_tags( apply_filters( 'the_content', $post->post_content ) ),
                'url'      => get_permalink( $post->ID ),
            ];
        }
        return $posts_data;
    }

    /**
     * (E) Fetch standard published pages, excluding policy pages (which are handled separately).
     */
    private function fetch_wordpress_pages_non_policy() {
        // For simplicity, we assume pages that don't contain 'policy' in the title or content are standard.
        // A better implementation would use a meta key or category check.
        $pages_data = [];
        $pages = get_pages( array(
            'post_status'    => 'publish',
            'posts_per_page' => 100,
        ) );

        foreach ( $pages as $page ) {
            if ( false === stripos( $page->post_title, 'policy' ) && false === stripos( $page->post_content, 'privacy policy' ) ) {
                 $pages_data[] = [
                    'id'       => $page->ID,
                    'type'     => $page->post_type,
                    'title'    => $page->post_title,
                    'content'  => wp_strip_all_tags( apply_filters( 'the_content', $page->post_content ) ),
                    'url'      => get_permalink( $page->ID ),
                ];
            }
        }
        return $pages_data;
    }

    /**
     * (F) Fetch policy-related pages (e.g., Privacy, Terms).
     */
        /**
     * (F) Fetches pages specifically designated for RAG AI Content.
     * Refinement: Targets pages either by common policy slugs (automated fallback) or
     * a user-defined list of Page IDs (user preference), avoiding the need for taxonomies on pages.
     */
    private function fetch_rag_ai_content_policy_pages() {
        $rag_content_data = [];
        
        // --- 1. Define Search Criteria ---
        
        // Placeholder for User-Defined IDs (Assumes this setting will be created in admin-settings.php)
        // In the final plugin, this would be loaded from a setting, e.g.:
        // $user_ids_setting = get_option('wp_rag_ai_chatbot_rag_page_ids', '');
        $user_ids_setting = ''; // Mock empty string for demonstration
        $target_ids = array_filter( array_map( 'absint', explode( ',', $user_ids_setting ) ) );

        // Fallback Slugs (Used only if $target_ids is empty)
        $policy_slugs = [
            'privacy-policy', 
            'terms-of-service', 
            'terms-and-conditions', 
            'terms-conditions',
            'terms-conditions-of-service',
            'cookie-policy',
            'data-protection-policy',
            'disclaimer',
            'refund-policy',
            'shipping-policy',
        ];

        $args = array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
        );

        // --- 2. Build Efficient WP_Query ---
        
        // A. If user has defined specific IDs, prioritize only those IDs (most precise).
        if ( ! empty( $target_ids ) ) {
            $args['post__in'] = $target_ids;
            
        } else {
            // B. If no IDs are defined, fall back to searching by common policy slugs.
            // This is efficient as it uses 'post_name__in' to query the database directly.
            $args['post_name__in'] = $policy_slugs;
        }
        
        // --- 3. Execute and Process Query ---

        $pages = get_posts( $args );

        // Process the small, filtered result set (limiting content to 1000 words)
        foreach ( $pages as $page ) {
            // Step A: Get the full content, apply filters (shortcodes), and strip HTML tags.
            $full_content = wp_strip_all_tags( apply_filters( 'the_content', $page->post_content ) );

            // Step B: Limit content to 1000 words for RAG indexing efficiency.
            $limited_content = wp_trim_words( $full_content, 1000, '' );

            $rag_content_data[] = [
                'id'       => $page->ID,
                'type'     => $page->post_type,
                'title'    => $page->post_title,
                'content'  => $limited_content,
                'url'      => get_permalink( $page->ID ),
            ];
        }

        return $rag_content_data;
    }


    /**
     * (G) Fetch custom post type content, typically FAQs.
     */
    private function fetch_faqs_cpt( $cpt_slug ) {
        $faqs_data = [];
        $faqs = get_posts( array(
            'post_type'      => $cpt_slug,
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'fields'         => 'ids',
        ) );

        foreach ( $faqs as $faq_id ) {
            $post = get_post( $faq_id );
            $faqs_data[] = [
                'id'       => $post->ID,
                'title' => $post->post_title,
                'content'   => wp_strip_all_tags( apply_filters( 'the_content', $post->post_content ) ),
                'url'      => get_permalink( $post->ID ),
            ];
        }
        return $faqs_data;
    }


    /**
     * (H) Fetch WooCommerce Products (New/Refined Helper)
     */
    private function fetch_woocommerce_products() {
        if ( ! function_exists( 'wc_get_products' ) ) {
            return []; // Safety check
        }

        $products_data = [];
        $products = wc_get_products( array(
            'limit'  => 100, // Limit to 100 to prevent massive memory usage
            'status' => 'publish',
            'return' => 'objects'
        ) );

        foreach ( $products as $product ) {
            $attributes = [];
            foreach ( $product->get_attributes() as $attribute_name => $attribute ) {
                $attributes[] = $attribute_name . ': ' . implode( ', ', $attribute->get_options() );
            }

            $products_data[] = [
                'id'          => $product->get_id(),
                'title'       => $product->get_name(),
                'description' => $product->get_description(), // Full description
                'short_description' => $product->get_short_description(),
                'price'       => $product->get_price(),
                'sku'         => $product->get_sku(),
                'attributes'  => implode( '; ', $attributes ),
                'dimensions'  => $product->get_dimensions( false ), // width, height, length
                'url'         => $product->get_permalink(),
                'image_url'   => wp_get_attachment_url( $product->get_image_id(), 'full' ), // Main product image URL
                'images_gallery'=> array_map( function( $image_id ) {
                    return wp_get_attachment_url( $image_id, 'full' );
                }, $product->get_gallery_image_ids() ),
            ];
        }

        return $products_data;
    }

        /**
     * (F) Fetches pages specifically designated for RAG AI Content.
     * Optimization: Uses a tax_query to filter pages at the database level,
     * avoiding the loading and iteration of all site pages.
     * This query runs only on manual admin-triggered data sync,
    * not on frontend requests, minimizing performance impact.

     */
    private function fetch_rag_ai_content_posts() {
        
        $rag_content_data = [];
        $rag_term_slug = 'ai-chatbot-content';

$args = array(
    'post_type'              => 'post',
    'post_status'            => 'publish',
    'posts_per_page'         => 100,    // Avoid -1 for performance
    'no_found_rows'          => true,   // Keeps query fast
    'fields'                 => 'ids',  // Only fetch IDs
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
    'tax_query'              => array(
        array(
            'taxonomy'         => 'post_tag',
            'field'            => 'slug',
            'terms'            => $rag_term_slug,
            'include_children' => false, 
        ),
    ),
);

// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
$pages = get_posts( $args );


        // 3. Process the smaller, filtered result set
        foreach ( $pages as $page ) {
            // Step A: Get the full content, apply filters (shortcodes), and strip HTML tags.
            $full_content = wp_strip_all_tags( apply_filters( 'the_content', $page->post_content ) );

            // Step B: Refinement - Limit content to 1000 words for RAG indexing efficiency.
            // We pass an empty string ('') as the third argument to avoid the default '...' ellipsis.
            $limited_content = wp_trim_words( $full_content, 1000, '' );

            $rag_content_data[] = [
                'id'       => $page->ID,
                'type'     => $page->post_type,
                'title'    => $page->post_title,
                'content'  => $limited_content, // Now uses the limited content
                'url'      => get_permalink( $page->ID ),
            ];
        }

        return $rag_content_data;
    }

}
