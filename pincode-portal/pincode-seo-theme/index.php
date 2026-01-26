<?php get_header(); ?>

<div class="container">
    <div class="search-box">
        <h1>Find Pincode Details</h1>
        <form action="<?php echo home_url('/'); ?>" method="get">
            <input type="text" name="s" placeholder="Search by Pincode or City..." />
            <button type="submit" style="padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Search</button>
        </form>
    </div>

    <div class="intro-text">
        <p>Welcome to the largest database of Indian Post Offices. Search for any pincode to find detailed information including address, phone number, and branch type.</p>
    </div>

    <!-- Ad Slot -->
    <div class="ad-slot">
        Google AdSense - Home Page
    </div>
</div>

<?php get_footer(); ?>
