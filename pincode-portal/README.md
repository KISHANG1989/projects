# India Pincode Directory - High Performance WordPress Solution

This package contains a complete, custom-architected solution for running a 155,000+ page Pincode Directory on shared hosting without bloating your WordPress database.

## Architecture
Instead of creating 155,000 WordPress "Posts" (which kills performance), this solution uses:
1.  **Custom Database Table (`wp_pincode_directory`)**: Stores all data efficiently.
2.  **Virtual Page Routing**: intercepting URLs like `/pincode/110001-...` and rendering them on the fly.
3.  **Lightweight Theme**: A custom theme designed for Core Web Vitals and SEO.

## Installation Instructions

### Phase 1: Preparation
1.  **Install WordPress** on your hosting.
2.  **Download Data**: Get the "All India Pincode Directory" CSV from [data.gov.in](https://data.gov.in).
    *   *Note: A sample file is included in `data/india_pincodes_sample.csv` for testing.*

### Phase 2: Database Setup
1.  Open your hosting **phpMyAdmin**.
2.  Import the file `setup_schema.sql`.
    *   *Important:* If your WordPress table prefix is NOT `wp_` (e.g., `xyz_`), edit the file to rename `wp_pincode_directory` to `xyz_pincode_directory` before importing.

### Phase 3: Install Theme
1.  Upload the `pincode-seo-theme` folder to your WordPress `wp-content/themes/` directory.
2.  Log in to WP Admin -> Appearance -> Themes.
3.  Activate **Pincode SEO Theme**.
4.  **CRITICAL:** Go to **Settings -> Permalinks** and just click **"Save Changes"**. This flushes the rewrite rules so the new URLs work.

### Phase 4: Import Data
1.  Open `importer/import.php` in a text editor.
2.  Update the **Database Configuration** at the top:
    ```php
    $db_host = 'localhost';
    $db_name = 'your_db_name';
    $db_user = 'your_db_user';
    $db_pass = 'your_db_password';
    $table_prefix = 'wp_'; // Change if needed
    ```
3.  Upload the `importer/` folder and your CSV file to your server.
4.  Run the import.
    *   **Option A (SSH/CLI - Recommended):** Run `php importer/import.php`
    *   **Option B (Browser):** Visit `yourwebsite.com/importer/import.php`. *Note: Large files might timeout in the browser. CLI is preferred.*

## Features
*   **SEO Optimized:** Dynamic Title Tags, Meta Descriptions, and JSON-LD Schema.
*   **Search:** Custom search engine that queries the pincode database directly.
*   **Silos:** District-based archives (e.g., `/district/new-delhi`) for strong internal linking.
*   **AdSense Ready:** Placeholders in `single-pincode.php` and `index.php`.

## Customization
*   Edit `single-pincode.php` to paste your real Google AdSense codes.
*   Edit `style.css` to change colors/layout.
