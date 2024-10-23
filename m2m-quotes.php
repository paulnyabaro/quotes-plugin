<?php
/*
Plugin Name: m2m Quotes
Description: Adds rotating quotes that switch every 24 hours with like/dislike, performance analytics, and share buttons.
Version: 1.0
Author: Your Name
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

// Schedule quote rotation (switch every 24 hours)
function m2m_schedule_rotation() {
    if (!wp_next_scheduled('m2m_rotate_quotes')) {
        wp_schedule_event(time(), 'daily', 'm2m_rotate_quotes');
    }
}
add_action('wp', 'm2m_schedule_rotation');

function m2m_rotate_quotes() {
    // Logic to rotate quotes, e.g., cycling between published quotes
    $quotes = get_posts(array('post_type' => 'm2m_quote', 'post_status' => 'publish'));
    if ($quotes) {
        // Mark the current active quote
        $current_quote_id = get_option('m2m_current_quote');
        if ($current_quote_id) {
            // Move to next quote or reset to the first one
            $next_index = array_search($current_quote_id, wp_list_pluck($quotes, 'ID')) + 1;
            $next_quote_id = isset($quotes[$next_index]) ? $quotes[$next_index]->ID : $quotes[0]->ID;
        } else {
            $next_quote_id = $quotes[0]->ID;
        }
        update_option('m2m_current_quote', $next_quote_id);
    }
}
add_action('m2m_rotate_quotes', 'm2m_rotate_quotes');

// Display the current active quote
function m2m_display_current_quote() {
    $quote_id = get_option('m2m_current_quote');
    if ($quote_id) {
        $quote = get_post($quote_id);
        $author = get_post_meta($quote_id, 'm2m_quote_author', true);
        $role = get_post_meta($quote_id, 'm2m_quote_role', true);
        $likes = get_post_meta($quote_id, 'm2m_likes', true) ?: 0;
        $dislikes = get_post_meta($quote_id, 'm2m_dislikes', true) ?: 0;

        // Display the quote
        echo '<div class="m2m-quote">';
        echo '<p>"' . esc_html($quote->post_content) . '"</p>';
        echo '<p><strong>- ' . esc_html($author) . ', ' . esc_html($role) . '</strong></p>';
        echo '<div class="m2m-like-dislike">';
        echo '<button class="m2m-like" data-id="' . $quote_id . '">Like (' . $likes . ')</button>';
        echo '<button class="m2m-dislike" data-id="' . $quote_id . '">Dislike (' . $dislikes . ')</button>';
        echo '</div>';
        echo '<div class="m2m-share">';
        echo '<button class="m2m-share-btn">Share</button>';
        echo '</div>';
        echo '</div>';
    }
}
add_shortcode('m2m_quote_display', 'm2m_display_current_quote');

// Handle AJAX for likes/dislikes
function m2m_handle_like_dislike() {
    check_ajax_referer('m2m_nonce', 'security');

    $post_id = intval($_POST['post_id']);
    $type = sanitize_text_field($_POST['type']);

    if ($type == 'like') {
        $likes = get_post_meta($post_id, 'm2m_likes', true) ?: 0;
        update_post_meta($post_id, 'm2m_likes', ++$likes);
        wp_send_json_success(array('likes' => $likes));
    } elseif ($type == 'dislike') {
        $dislikes = get_post_meta($post_id, 'm2m_dislikes', true) ?: 0;
        update_post_meta($post_id, 'm2m_dislikes', ++$dislikes);
        wp_send_json_success(array('dislikes' => $dislikes));
    }
}
add_action('wp_ajax_m2m_like_dislike', 'm2m_handle_like_dislike');
add_action('wp_ajax_nopriv_m2m_like_dislike', 'm2m_handle_like_dislike');

// Admin meta boxes for adding author and role
function m2m_quote_meta_boxes() {
    add_meta_box('m2m_quote_meta', 'Quote Details', 'm2m_quote_meta_callback', 'm2m_quote', 'normal', 'high');
}
add_action('add_meta_boxes', 'm2m_quote_meta_boxes');

function m2m_quote_meta_callback($post) {
    $author = get_post_meta($post->ID, 'm2m_quote_author', true);
    $role = get_post_meta($post->ID, 'm2m_quote_role', true);

    echo '<label for="m2m_quote_author">Author:</label>';
    echo '<input type="text" name="m2m_quote_author" value="' . esc_attr($author) . '" />';
    echo '<label for="m2m_quote_role">Role:</label>';
    echo '<input type="text" name="m2m_quote_role" value="' . esc_attr($role) . '" />';
}

function m2m_save_quote_meta($post_id) {
    if (array_key_exists('m2m_quote_author', $_POST)) {
        update_post_meta($post_id, 'm2m_quote_author', sanitize_text_field($_POST['m2m_quote_author']));
    }
    if (array_key_exists('m2m_quote_role', $_POST)) {
        update_post_meta($post_id, 'm2m_quote_role', sanitize_text_field($_POST['m2m_quote_role']));
    }
}
add_action('save_post', 'm2m_save_quote_meta');

