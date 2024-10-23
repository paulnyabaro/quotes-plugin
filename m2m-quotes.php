<?php
/*
Plugin Name: m2m Quotes
Description: Adds rotating quotes that switch every 24 hours with like/dislike, performance analytics, customizable buttons, and share features.
Version: 1.1
Author: Paul Nyabaro
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enqueue plugin scripts and styles
function m2m_enqueue_scripts() {
    wp_enqueue_style('m2m_quotes_styles', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('m2m_quotes_scripts', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), null, true);

    // Localize for Ajax
    wp_localize_script('m2m_quotes_scripts', 'm2m_ajax', array(
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('m2m_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'm2m_enqueue_scripts');

// Create custom post type for quotes
function m2m_create_quote_post_type() {
    $labels = array(
        'name' => 'Quotes',
        'singular_name' => 'Quote',
        'menu_name' => 'm2m Quotes',
        'name_admin_bar' => 'Quote',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Quote',
        'edit_item' => 'Edit Quote',
        'new_item' => 'New Quote',
        'view_item' => 'View Quote',
        'all_items' => 'All Quotes',
        'search_items' => 'Search Quotes',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
        'menu_icon' => 'dashicons-format-quote',
    );
    register_post_type('m2m_quote', $args);
}
add_action('init', 'm2m_create_quote_post_type');

// Add shortcode for displaying the quote
function m2m_display_current_quote($atts) {
    ob_start();

    $quote_id = get_option('m2m_current_quote');
    if ($quote_id) {
        $quote = get_post($quote_id);
        $author = get_post_meta($quote_id, 'm2m_quote_author', true);
        $role = get_post_meta($quote_id, 'm2m_quote_role', true);
        $likes = get_post_meta($quote_id, 'm2m_likes', true) ?: 0;
        $dislikes = get_post_meta($quote_id, 'm2m_dislikes', true) ?: 0;
        $button1 = get_post_meta($quote_id, 'm2m_button1_url', true);
        $button2 = get_post_meta($quote_id, 'm2m_button2_url', true);
        $button3 = get_post_meta($quote_id, 'm2m_button3_url', true);
        $button4 = get_post_meta($quote_id, 'm2m_button4_url', true);

        echo '<div class="m2m-quote">';
        echo '<p>"' . esc_html($quote->post_content) . '"</p>';
        echo '<p><strong>- ' . esc_html($author) . ', ' . esc_html($role) . '</strong></p>';
        echo '<div class="m2m-like-dislike">';
        echo '<button class="m2m-like" data-id="' . $quote_id . '"><i class="dashicons dashicons-thumbs-up"></i> Like (' . $likes . ')</button>';
        echo '<button class="m2m-dislike" data-id="' . $quote_id . '"><i class="dashicons dashicons-thumbs-down"></i> Dislike (' . $dislikes . ')</button>';
        echo '</div>';
        echo '<div class="m2m-buttons">';
        echo '<a href="' . esc_url($button1) . '" class="m2m-btn"><img src="' . plugin_dir_url(__FILE__) . 'assets/img/button1.png" />Button 1</a>';
        echo '<a href="' . esc_url($button2) . '" class="m2m-btn"><img src="' . plugin_dir_url(__FILE__) . 'assets/img/button2.png" />Button 2</a>';
        echo '<a href="' . esc_url($button3) . '" class="m2m-btn"><img src="' . plugin_dir_url(__FILE__) . 'assets/img/button3.png" />Button 3</a>';
        echo '<a href="' . esc_url($button4) . '" class="m2m-btn"><img src="' . plugin_dir_url(__FILE__) . 'assets/img/share.png" />Share</a>';
        echo '</div>';
        echo '</div>';
        
    }

    return ob_get_clean();
}
add_shortcode('m2m_quote_display', 'm2m_display_current_quote');

// Admin meta boxes for adding author, role, and button URLs
function m2m_quote_meta_boxes() {
    add_meta_box('m2m_quote_meta', 'Quote Details', 'm2m_quote_meta_callback', 'm2m_quote', 'normal', 'high');
}
add_action('add_meta_boxes', 'm2m_quote_meta_boxes');

function m2m_quote_meta_callback($post) {
    $author = get_post_meta($post->ID, 'm2m_quote_author', true);
    $role = get_post_meta($post->ID, 'm2m_quote_role', true);
    $button1 = get_post_meta($post->ID, 'm2m_button1_url', true);
    $button2 = get_post_meta($post->ID, 'm2m_button2_url', true);
    $button3 = get_post_meta($post->ID, 'm2m_button3_url', true);
    $button4 = get_post_meta($post->ID, 'm2m_button4_url', true);

    echo '<label for="m2m_quote_author">Author:</label>';
    echo '<input type="text" name="m2m_quote_author" value="' . esc_attr($author) . '" />';
    echo '<label for="m2m_quote_role">Role:</label>';
    echo '<input type="text" name="m2m_quote_role" value="' . esc_attr($role) . '" />';

    echo '<h3>Buttons</h3>';
    echo '<label for="m2m_button1_url">Button 1 URL:</label>';
    echo '<input type="url" name="m2m_button1_url" value="' . esc_attr($button1) . '" />';
    echo '<label for="m2m_button2_url">Button 2 URL:</label>';
    echo '<input type="url" name="m2m_button2_url" value="' . esc_attr($button2) . '" />';
    echo '<label for="m2m_button3_url">Button 3 URL:</label>';
    echo '<input type="url" name="m2m_button3_url" value="' . esc_attr($button3) . '" />';
    echo '<label for="m2m_button4_url">Button 4 (Share) URL:</label>';
    echo '<input type="url" name="m2m_button4_url" value="' . esc_attr($button4) . '" />';
}

function m2m_save_quote_meta($post_id) {
    if (array_key_exists('m2m_quote_author', $_POST)) {
        update_post_meta($post_id, 'm2m_quote_author', sanitize_text_field($_POST['m2m_quote_author']));
    }
    if (array_key_exists('m2m_quote_role', $_POST)) {
        update_post_meta($post_id, 'm2m_quote_role', sanitize_text_field($_POST['m2m_quote_role']));
    }
    if (array_key_exists('m2m_button1_url', $_POST)) {
        update_post_meta($post_id, 'm2m_button1_url', esc_url_raw($_POST['m2m_button1_url']));
    }
    if (array_key_exists('m2m_button2_url', $_POST)) {
        update_post_meta($post_id, 'm2m_button2_url', esc_url_raw($_POST['m2m_button2_url']));
    }
    if (array_key_exists('m2m_button3_url', $_POST)) {
        update_post_meta($post_id, 'm2m_button3_url', esc_url_raw($_POST['m2m_button3_url']));
    }
    if (array_key_exists('m2m_button4_url', $_POST)) {
        update_post_meta($post_id, 'm2m_button4_url', esc_url_raw($_POST['m2m_button4_url']));
    }
}
add_action('save_post', 'm2m_save_quote_meta');

// Plugin settings page with tabs
function m2m_add_admin_menu() {
    add_menu_page('m2m Quotes Settings', 'm2m Quotes Settings', 'manage_options', 'm2m-quotes-settings', 'm2m_quotes_settings_page');
}
add_action('admin_menu', 'm2m_add_admin_menu');

function m2m_quotes_settings_page() {
    ?>
    <div class="wrap">
        <h1>m2m Quotes Settings</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=m2m-quotes-settings&tab=general" class="nav-tab">General Settings</a>
            <a href="?page=m2m-quotes-settings&tab=buttons" class="nav-tab">Button Settings</a>
        </h2>

        <?php
        if (isset($_GET['tab']) && $_GET['tab'] == 'buttons') {
            m2m_button_settings_page();
        } else {
            m2m_general_settings_page();
        }
        ?>
    </div>
    <?php
}

function m2m_general_settings_page() {
    $quotes = get_posts(array(
        'post_type' => 'm2m_quote',
        'numberposts' => -1
    ));

    if ($quotes) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Quote</th><th>Author</th><th>Likes</th><th>Dislikes</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($quotes as $quote) {
            $likes = get_post_meta($quote->ID, 'm2m_likes', true) ?: 0;
            $dislikes = get_post_meta($quote->ID, 'm2m_dislikes', true) ?: 0;
            $author = get_post_meta($quote->ID, 'm2m_quote_author', true);

            echo '<tr>';
            echo '<td>' . esc_html($quote->post_content) . '</td>';
            echo '<td>' . esc_html($author) . '</td>';
            echo '<td>' . esc_html($likes) . '</td>';
            echo '<td>' . esc_html($dislikes) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No quotes found.</p>';
    }
}

function m2m_button_settings_page() {
    echo '<p>Button settings for m2m Quotes will be displayed here.</p>';
}


function m2m_handle_like_dislike() {
    // Check nonce for security
    check_ajax_referer('m2m_nonce', 'nonce');

    if (!isset($_POST['quote_id']) || !isset($_POST['type'])) {
        wp_send_json_error(array('message' => 'Invalid request'));
    }

    $quote_id = intval($_POST['quote_id']);
    $type = sanitize_text_field($_POST['type']);

    // Get the current like/dislike count
    $likes = get_post_meta($quote_id, 'm2m_likes', true) ?: 0;
    $dislikes = get_post_meta($quote_id, 'm2m_dislikes', true) ?: 0;

    // Increment likes or dislikes based on the type
    if ($type === 'like') {
        $likes++;
        update_post_meta($quote_id, 'm2m_likes', $likes);
    } elseif ($type === 'dislike') {
        $dislikes++;
        update_post_meta($quote_id, 'm2m_dislikes', $dislikes);
    } else {
        wp_send_json_error(array('message' => 'Invalid action'));
    }

    wp_send_json_success();
}

add_action('wp_ajax_m2m_handle_like_dislike', 'm2m_handle_like_dislike');
add_action('wp_ajax_nopriv_m2m_handle_like_dislike', 'm2m_handle_like_dislike');