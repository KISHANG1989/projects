<?php
/**
 * Template Name: Search Results
 */

get_header();
$search_query = get_search_query();

global $wpdb;
$table_name = $wpdb->prefix . 'pincode_directory';

// Custom Search Query
// We search: Pincode (exact/like), Office Name (like), District (like)
$query = $wpdb->prepare(
    "SELECT * FROM $table_name
    WHERE pincode LIKE %s
    OR officename LIKE %s
    OR districtname LIKE %s
    LIMIT 50",
    '%' . $wpdb->esc_like($search_query) . '%',
    '%' . $wpdb->esc_like($search_query) . '%',
    '%' . $wpdb->esc_like($search_query) . '%'
);

$results = $wpdb->get_results($query);
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo home_url(); ?>">Home</a> &gt;
        <span>Search Results for "<?php echo esc_html($search_query); ?>"</span>
    </div>

    <h1>Search Results</h1>

    <div class="row">
        <?php if ($results): ?>
            <p>Found <?php echo count($results); ?> results.</p>
            <div class="list-group">
            <?php foreach ($results as $item): ?>
                <div class="pincode-card">
                    <h3>
                        <a href="<?php echo home_url('/pincode/' . $item->slug); ?>">
                            <?php echo esc_html($item->officename); ?> (<?php echo esc_html($item->pincode); ?>)
                        </a>
                    </h3>
                    <p>
                        <?php echo esc_html($item->taluk); ?>,
                        <a href="<?php echo home_url('/district/' . sanitize_title($item->districtname)); ?>">
                            <?php echo esc_html($item->districtname); ?>
                        </a>,
                        <?php echo esc_html($item->statename); ?>
                    </p>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No results found for "<?php echo esc_html($search_query); ?>". Try a different pincode or city.</p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
