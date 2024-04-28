<?php 
function rv_enqueue_scripts() {
    wp_enqueue_script('rv-calculator', plugin_dir_url(__FILE__) . 'calculator.js', array('jquery'), null, true);

    // Initialize prices and fetch only if a post is available
    $regular_price = $dealer_price = $sale_price = 0;
    $loan_period_months = 60; // Default loan period months
    $model_year = 0; // Initialize model year


    if (is_singular('rv-products')) {
        global $post;
        $regular_price_key = get_option('rvc_financing_regular_price_meta', 'regular_price_meta');
        $dealer_price_key = get_option('rvc_financing_dealer_price_meta', 'dealer_price_meta');
        $sale_price_key = get_option('rvc_financing_sale_price_meta', 'sale_price_meta');

        $regular_price = get_post_meta($post->ID, $regular_price_key, true);
        $dealer_price = get_post_meta($post->ID, $dealer_price_key, true);
        $sale_price = get_post_meta($post->ID, $sale_price_key, true);
        $model_year = get_post_meta($post->ID, 'years', true);
        

        // Retrieve the current post's "years" meta
        $years_meta = get_post_meta($post->ID, 'years', true);
        if (!empty($years_meta)) {
            // Get the corresponding amortization length for the current year from plugin settings
            $amortization_lengths = get_option('rvc_financing_amortization_years', array());
            $loan_period_months = isset($amortization_lengths[$years_meta]) ? $amortization_lengths[$years_meta] : $loan_period_months;
        }
    }

    $prices = array($regular_price, $dealer_price, $sale_price);
    $prices = array_map('floatval', array_filter($prices)); // Filter out empty values and convert to float
    $lowest_price = !empty($prices) ? min($prices) : 0;

    $tax_rate = get_option('rvc_financing_tax_rate', 5) / 100 + 1; // Default 5%, convert to multiplicative factor
    $apr = get_option('rvc_financing_apr', 8.99); // Default APR

    // Prepare data to pass to JS
    $data = array(
        'amount' => $lowest_price * $tax_rate,
        'apr' => $apr,
        'loan_period_months' => $loan_period_months,
        'model_year' => $model_year // Add this line
    );

    // Fetch and add the dynamic amortization lengths based on the registered settings
    $amortization_data = array(
        'amortizationLengths' => array() // Initialize an array to hold the dynamic amortization lengths
    );

    $years = range(2025, 2006); // Get the range of years
    foreach ($years as $year) {
        $amortization_length = get_option('rvc_financing_amortization_length_' . $year, 0); // Fetch the length for each year
        $amortization_data['amortizationLengths'][$year] = $amortization_length; // Add to the data array
    }

    // Combine the existing data with the amortization data
    $data = array_merge($data, $amortization_data);

    wp_localize_script('rv-calculator', 'rvCalcData', $data);
}

add_action('wp_enqueue_scripts', 'rv_enqueue_scripts', 100);
