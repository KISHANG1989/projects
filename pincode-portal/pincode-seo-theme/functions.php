<?php

// Register Rewrite Rule
function pincode_rewrite_rules() {
    add_rewrite_rule('^pincode/([^/]+)/?$', 'index.php?pincode_slug=$matches[1]', 'top');
    add_rewrite_rule('^district/([^/]+)/?$', 'index.php?district_slug=$matches[1]', 'top');
}
add_action('init', 'pincode_rewrite_rules');

// Register Query Var
function pincode_query_vars($vars) {
    $vars[] = 'pincode_slug';
    $vars[] = 'district_slug';
    return $vars;
}
add_filter('query_vars', 'pincode_query_vars');

// Template Redirect
function pincode_template_redirect() {
    $slug = get_query_var('pincode_slug');
    if ($slug) {
        include get_template_directory() . '/single-pincode.php';
        exit;
    }

    $district = get_query_var('district_slug');
    if ($district) {
        include get_template_directory() . '/archive-district.php';
        exit;
    }
}
add_action('template_redirect', 'pincode_template_redirect');

// Helper: Get Pincode Data
function get_pincode_data($slug) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pincode_directory';

    // Prepare statement to prevent SQL injection
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE slug = %s", $slug);
    return $wpdb->get_row($query);
}

// Helper: Get Related Pincodes (Same District)
function get_related_pincodes($district, $exclude_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pincode_directory';

    $query = $wpdb->prepare(
        "SELECT officename, pincode, slug FROM $table_name WHERE districtname = %s AND id != %d LIMIT 10",
        $district,
        $exclude_id
    );
    return $wpdb->get_results($query);
}

// Dynamic Title
function pincode_seo_title($title) {
    $slug = get_query_var('pincode_slug');
    if ($slug) {
        $data = get_pincode_data($slug);
        if ($data) {
            return "{$data->pincode} {$data->officename} Post Office - {$data->districtname}, {$data->statename}";
        }
    }
    return $title;
}
add_filter('pre_get_document_title', 'pincode_seo_title');

// Meta Description
function pincode_seo_meta() {
    $slug = get_query_var('pincode_slug');
    if ($slug) {
        $data = get_pincode_data($slug);
        if ($data) {
            $desc = "Details of {$data->officename} (Pin Code {$data->pincode}) in {$data->taluk}, {$data->districtname}. Check address, phone number, and branch type.";
            echo '<meta name="description" content="' . esc_attr($desc) . '" />' . "\n";
        }
    }
}
add_action('wp_head', 'pincode_seo_meta');

// Theme Setup
function pincode_theme_setup() {
    add_theme_support('title-tag');
}
add_action('after_setup_theme', 'pincode_theme_setup');

// Flush Rewrite Rules on Activation
add_action('after_switch_theme', function() {
    pincode_rewrite_rules();
    flush_rewrite_rules();
});
