<?php
/**
 * Template Name: District Archive
 */

$district_slug = get_query_var('district_slug');
// Convert slug "new-delhi" -> "New Delhi" (Approximate)
$district_name_approx = ucwords(str_replace('-', ' ', $district_slug));

global $wpdb;
$table_name = $wpdb->prefix . 'pincode_directory';

// Fetch all offices in this district
// Note: This matches strictly on the generated name.
// A more robust system would have a districts table.
$query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE districtname LIKE %s ORDER BY officename ASC LIMIT 100",
    $district_name_approx
);
$results = $wpdb->get_results($query);

get_header();
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo home_url(); ?>">Home</a> &gt;
        <span>District</span> &gt;
        <span><?php echo esc_html($district_name_approx); ?></span>
    </div>

    <h1>Pincodes in <?php echo esc_html($district_name_approx); ?></h1>

    <div class="row">
        <?php if ($results): ?>
            <div class="list-group">
            <?php foreach ($results as $item): ?>
                <div class="pincode-card">
                    <h3>
                        <a href="<?php echo home_url('/pincode/' . $item->slug); ?>">
                            <?php echo esc_html($item->officename); ?> (<?php echo esc_html($item->pincode); ?>)
                        </a>
                    </h3>
                    <p>
                        <?php echo esc_html($item->taluk); ?>, <?php echo esc_html($item->statename); ?>
                    </p>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No pincodes found for this district. Try searching.</p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
