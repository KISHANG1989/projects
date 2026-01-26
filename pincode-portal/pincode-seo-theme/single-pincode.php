<?php
/**
 * Template Name: Single Pincode View
 */

$slug = get_query_var('pincode_slug');
$data = get_pincode_data($slug);

if (!$data) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit;
}

$related = get_related_pincodes($data->districtname, $data->id);

get_header();
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo home_url(); ?>">Home</a> &gt;
        <span><?php echo esc_html($data->statename); ?></span> &gt;
        <a href="<?php echo home_url('/district/' . sanitize_title($data->districtname)); ?>"><?php echo esc_html($data->districtname); ?></a> &gt;
        <span><?php echo esc_html($data->pincode); ?></span>
    </div>

    <div class="row">
        <div class="main-content">
            <h1><?php echo esc_html($data->officename); ?> (<?php echo esc_html($data->pincode); ?>)</h1>
            <p class="lead">
                Post Office details for <strong><?php echo esc_html($data->officename); ?></strong> in <?php echo esc_html($data->districtname); ?> district, <?php echo esc_html($data->statename); ?>.
            </p>

            <!-- Ad Slot Top -->
            <div class="ad-slot">
                Google AdSense - Top Leaderboard
            </div>

            <div class="pincode-card">
                <h2>Office Details</h2>
                <table class="data-table">
                    <tr><th>Pincode</th><td><?php echo esc_html($data->pincode); ?></td></tr>
                    <tr><th>Office Name</th><td><?php echo esc_html($data->officename); ?></td></tr>
                    <tr><th>Office Type</th><td><?php echo esc_html($data->pincode_type); ?></td></tr>
                    <tr><th>Delivery Status</th><td><?php echo esc_html($data->deliverystatus); ?></td></tr>
                    <tr><th>Division</th><td><?php echo esc_html($data->divisionname); ?></td></tr>
                    <tr><th>Region</th><td><?php echo esc_html($data->regionname); ?></td></tr>
                    <tr><th>Circle</th><td><?php echo esc_html($data->circlename); ?></td></tr>
                    <tr><th>Taluk</th><td><?php echo esc_html($data->taluk); ?></td></tr>
                    <tr><th>District</th><td><?php echo esc_html($data->districtname); ?></td></tr>
                    <tr><th>State</th><td><?php echo esc_html($data->statename); ?></td></tr>
                    <tr><th>Telephone</th><td><?php echo esc_html($data->telephone); ?></td></tr>
                </table>
            </div>

            <!-- Ad Slot Middle -->
            <div class="ad-slot">
                Google AdSense - In Content
            </div>

            <?php if ($related): ?>
            <div class="related-pincodes">
                <h3>Other Post Offices in <?php echo esc_html($data->districtname); ?></h3>
                <ul>
                    <?php foreach ($related as $item): ?>
                        <li>
                            <a href="<?php echo home_url('/pincode/' . $item->slug); ?>">
                                <?php echo esc_html($item->officename); ?> (<?php echo esc_html($item->pincode); ?>)
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "PostOffice",
  "name": "<?php echo esc_js($data->officename); ?> Post Office",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?php echo esc_js($data->taluk); ?>",
    "addressLocality": "<?php echo esc_js($data->districtname); ?>",
    "addressRegion": "<?php echo esc_js($data->statename); ?>",
    "postalCode": "<?php echo esc_js($data->pincode); ?>",
    "addressCountry": "IN"
  },
  "telephone": "<?php echo esc_js($data->telephone); ?>"
}
</script>

<?php get_footer(); ?>
