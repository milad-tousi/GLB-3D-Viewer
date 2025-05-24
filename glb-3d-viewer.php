<?php
/*
Plugin Name: GLB 3D Viewer
Description: Presenting 3D models in GLB format in pages and posts using shortcode.
Version: 1.1
Author: Milad Tousi
*/

defined('ABSPATH') or die('No script kiddies please!');

// Enqueue Scripts
function glb_3d_viewer_enqueue_scripts() {
    wp_enqueue_script('three-js', 'https://cdn.jsdelivr.net/npm/three@0.138.3/build/three.min.js', [], null, true);
    wp_enqueue_script('glb-loader', 'https://cdn.jsdelivr.net/npm/three@0.138.3/examples/js/loaders/GLTFLoader.min.js', ['three-js'], null, true);
    wp_enqueue_script('orbit-controls', 'https://cdn.jsdelivr.net/npm/three@0.138.3/examples/js/controls/OrbitControls.min.js', ['three-js'], null, true);
    wp_enqueue_script('glb-viewer-script', plugin_dir_url(__FILE__) . 'viewer.js', ['three-js', 'glb-loader', 'orbit-controls'], null, true);
}
add_action('wp_enqueue_scripts', 'glb_3d_viewer_enqueue_scripts');

// Shortcode Renderer
function glb_viewer_model_shortcode($atts, $content = null, $tag = '') {
    if (!preg_match('/glb_viewer_(\d+)/', $tag, $matches)) return '';
    $post_id = intval($matches[1]);
    if (get_post_type($post_id) !== 'glb_model') return '';

    $src = get_post_meta($post_id, '_glb_src', true);
    $width = get_post_meta($post_id, '_glb_width', true) ?: '100%';
    $height = get_post_meta($post_id, '_glb_height', true) ?: '500';
    $rotate = get_post_meta($post_id, '_glb_rotate', true) ?: 'false';
    $bg = get_post_meta($post_id, '_glb_bg', true) ?: '#000000';
    $light_type = get_post_meta($post_id, '_glb_light_type', true) ?: 'hemisphere';
    $light_color = get_post_meta($post_id, '_glb_light_color', true) ?: '#ffffff';
    $light_intensity = get_post_meta($post_id, '_glb_light_intensity', true) ?: '1.0';

    return '<div class="glb-viewer-container" data-src="' . esc_url($src) . '" data-auto_rotate="' . esc_attr($rotate) . '" data-background="' . esc_attr($bg) . '" data-light_type="' . esc_attr($light_type) . '" data-light_color="' . esc_attr($light_color) . '" data-light_intensity="' . esc_attr($light_intensity) . '" style="width: ' . esc_attr($width) . '; height: ' . esc_attr($height) . 'px;"></div>';
}
function glb_register_dynamic_shortcodes() {
    $posts = get_posts(['post_type' => 'glb_model', 'numberposts' => -1]);
    foreach ($posts as $post) {
        add_shortcode('glb_viewer_' . $post->ID, 'glb_viewer_model_shortcode');
    }
}
add_action('init', 'glb_register_dynamic_shortcodes');

// Custom Post Type
function glb_register_model_cpt() {
    register_post_type('glb_model', [
        'public' => false,
        'show_ui' => true,
        'supports' => ['title'],
        'menu_icon' => 'dashicons-format-gallery',
    ]);
}
add_action('init', 'glb_register_model_cpt');

// Handle Delete Action
add_action('admin_post_glb_delete_model', function () {
    if (
        !current_user_can('manage_options') ||
        !isset($_GET['id']) ||
        !wp_verify_nonce($_GET['_wpnonce'], 'glb_delete_model_' . $_GET['id'])
    ) {
        wp_die('Unauthorized or invalid request');
    }

    $id = intval($_GET['id']);
    wp_delete_post($id, true);
    
    wp_redirect(admin_url('admin.php?page=glb-viewer-models'));
    exit;
});


// Save Model AJAX
function glb_save_model_ajax() {
    $src = sanitize_text_field($_POST['src']);
    $width = sanitize_text_field($_POST['width']);
    $height = sanitize_text_field($_POST['height']);
    $auto_rotate = sanitize_text_field($_POST['auto_rotate']);
    $background = sanitize_hex_color($_POST['background']);
    $light_type = sanitize_text_field($_POST['light_type']);
    $light_color = sanitize_hex_color($_POST['light_color']);
    $light_intensity = floatval($_POST['light_intensity']);

    $post_id = wp_insert_post([
        'post_type' => 'glb_model',
        'post_status' => 'publish',
        'post_title' => sanitize_text_field($_POST['title']) ?: 'GLB Model ' . time(),
    ]);

    update_post_meta($post_id, '_glb_src', $src);
    update_post_meta($post_id, '_glb_width', $width);
    update_post_meta($post_id, '_glb_height', $height);
    update_post_meta($post_id, '_glb_rotate', $auto_rotate);
    update_post_meta($post_id, '_glb_bg', $background);
    update_post_meta($post_id, '_glb_light_type', $light_type);
    update_post_meta($post_id, '_glb_light_color', $light_color);
    update_post_meta($post_id, '_glb_light_intensity', $light_intensity);

    wp_send_json_success('[glb_viewer_' . $post_id . ']');
}
add_action('wp_ajax_glb_save_model', 'glb_save_model_ajax');

// Admin Scripts
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'glb-viewer-settings') !== false) {
        wp_enqueue_media();
        wp_enqueue_script('three-js', 'https://cdn.jsdelivr.net/npm/three@0.138.3/build/three.min.js', [], null, true);
        wp_enqueue_script('glb-loader', 'https://cdn.jsdelivr.net/npm/three@0.138.3/examples/js/loaders/GLTFLoader.min.js', ['three-js'], null, true);
        wp_enqueue_script('orbit-controls', 'https://cdn.jsdelivr.net/npm/three@0.138.3/examples/js/controls/OrbitControls.min.js', ['three-js'], null, true);
        wp_enqueue_script('glb-admin-js', plugin_dir_url(__FILE__) . 'admin.js', ['jquery', 'three-js', 'glb-loader', 'orbit-controls'], null, true);
        wp_localize_script('glb-admin-js', 'GLB_VARS', ['ajax_url' => admin_url('admin-ajax.php')]);
    }
});



// Admin Menu Pages
function glb_3d_viewer_settings_page() {
    include plugin_dir_path(__FILE__) . 'admin-settings.php';
}

function glb_3d_viewer_models_page() {
    include plugin_dir_path(__FILE__) . 'admin-models.php';
}

add_action('admin_menu', function () {
    add_menu_page('GLB Viewer', 'GLB Viewer', 'manage_options', 'glb-viewer-settings', 'glb_3d_viewer_settings_page', 'dashicons-format-gallery', 25);
    add_submenu_page('glb-viewer-settings', 'Models', 'Models', 'manage_options', 'glb-viewer-models', 'glb_3d_viewer_models_page');
});

function allow_glb_upload($mimes) {
    $mimes['glb'] = 'model/gltf-binary';
    return $mimes;
}
add_filter('upload_mimes', 'allow_glb_upload');

function allow_glb_filetype_check($data, $file, $filename, $mimes) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if ($ext === 'glb') {
        $data['ext'] = 'glb';
        $data['type'] = 'model/gltf-binary';
    }
    return $data;
}
add_filter('wp_check_filetype_and_ext', 'allow_glb_filetype_check', 10, 4);

add_filter('wp_get_attachment_url', function($url) {
    return preg_replace('/^http:/i', 'https:', $url);
});
