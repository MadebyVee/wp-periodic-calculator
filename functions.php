<?php
// functions.php

// Enqueue your CSS file
function enqueue_payment_styles() {
    if (!is_admin()) {
        wp_enqueue_style('payment-styles', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0', 'all');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_payment_styles');

//Enqueue Scripts and Styles (Optional)
function enqueue_calculator_scripts() {
     wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('jquery'); // Ensure jQuery is included
}
add_action('wp_enqueue_scripts', 'enqueue_calculator_scripts');



// Data Retrieval Section

// Hardcoded 'model_year' for now, using the 'years' meta key
function get_model_year() {
    return get_post_meta(get_the_ID(), 'years', true);
}

// Fetch amortization period from 'rvcpc_amortization' for the given model year
function get_amortization_period($model_year) {
    global $wpdb;
    $amortization_table = $wpdb->prefix . 'rvcpc_amortization';
    $result = $wpdb->get_row($wpdb->prepare("SELECT amortization_months FROM $amortization_table WHERE vehicle_year = %d", $model_year));

    if (!$result) {
        error_log("Error retrieving amortization period for model year: $model_year");
    }
    return $result ? $result->amortization_months : null;
}

// Fetch loan term from 'rvcpc_amortization' for the given model year
function get_loan_term($model_year) {
    global $wpdb;
    $amortization_table = $wpdb->prefix . 'rvcpc_amortization';
    $result = $wpdb->get_row($wpdb->prepare("SELECT term_months FROM $amortization_table WHERE vehicle_year = %d", $model_year));

    if (!$result) {
        error_log("Error retrieving loan term for model year: $model_year");
    }
    return $result ? $result->term_months : null;
}

// Fetch specific setting values from 'rvcpc_settings'
function get_setting_value($setting_key) {
    global $wpdb;
    $settings_table = $wpdb->prefix . 'rvcpc_settings';
    $allowed_keys = array('interest_rate', 'tax_rate', 'loan_fee', 'taxed_fee');

    if (!in_array($setting_key, $allowed_keys)) {
        error_log("Invalid setting key requested: $setting_key");
        return null;
    }

    $result = $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM $settings_table WHERE setting_key = %s", $setting_key));

    if (!$result) {
        error_log("Error retrieving setting value for key $setting_key.");
    }
    return $result;
}

// Fetch RV type fees from 'rvcpc_rvtypes_fees' based on the current post's 'rv-types' taxonomy
function get_rv_type_fees() {
    global $wpdb, $post;

    // Get the RV type from the current post's 'rv-types' taxonomy
    $rv_type_terms = wp_get_post_terms($post->ID, 'rv-types');
    if (is_wp_error($rv_type_terms) || empty($rv_type_terms)) {
        error_log("Error: No RV type found for the post ID {$post->ID}.");
        return null;
    }

    // Assume the first term is the RV type
    $rv_type = $rv_type_terms[0]->name;

    // Query the 'rvcpc_rvtypes_fees' table to get the RV type fee
    $rvtypes_fees_table = $wpdb->prefix . 'rvcpc_rvtypes_fees';
    $result = $wpdb->get_var($wpdb->prepare("SELECT rv_type_fee FROM $rvtypes_fees_table WHERE rv_type_name = %s", $rv_type));

    if (!$result) {
        error_log("Error retrieving RV type fees for RV type: {$rv_type}.");
        return null;
    }

    return $result;
}

// Function to fetch all meta keys from 'rvcpc_meta' table and match with post meta keys
function get_matching_meta_keys($post_id) {
    global $wpdb;
    $meta_keys_table = $wpdb->prefix . 'rvcpc_meta';

    // Fetch all plugin meta keys from 'rvcpc_meta' table
    $plugin_meta_keys = $wpdb->get_col("SELECT meta_key_name FROM $meta_keys_table");

    // Fetch all meta keys from the current post
    $post_meta_keys = array_keys(get_post_meta($post_id));

    // Find the matching keys
    $common_keys = array_intersect($plugin_meta_keys, $post_meta_keys);

    if (empty($common_keys)) {
        error_log("No matching meta keys found for post ID: $post_id");
    }
    return $common_keys;
}

// Payment Calculation Section

function calculate_payment($lowest_price) {
    // Fetching the necessary data
    $data = array(
        'regular_price' => $lowest_price,
        'fees' => get_setting_value('loan_fee'),
        'fees_rate' => get_setting_value('taxed_fee'),
        'tax_rate' => get_setting_value('tax_rate') / 100,
        'interest_rate' => get_setting_value('interest_rate') / 100 / 26,
        'model_year' => get_model_year(),
    );

    error_log('Data Fetched: ' . print_r($data, true));

    // Sanitize inputs and log data for debugging
    $principal = !empty($data['regular_price']) ? floatval($data['regular_price']) : 0;
    $fees = !empty($data['fees']) ? floatval($data['fees']) : 0;
    $fees_rate = !empty($data['fees_rate']) ? floatval($data['fees_rate']) : 0;
    $tax_rate = isset($data['tax_rate']) && $data['tax_rate'] > 0 ? floatval($data['tax_rate']) : 0;
    $interest_rate_per_period = !empty($data['interest_rate']) ? floatval($data['interest_rate']) : 0;
    $rv_type_fees = !empty($data['rv_type_fees']) ? floatval($data['rv_type_fees']) : 0;
    $model_year = !empty($data['model_year']) ? intval($data['model_year']) : 0;

    if ($principal <= 0) return 'Error: Principal must be greater than zero.';
    if ($interest_rate_per_period <= 0) return 'Error: Interest rate must be greater than zero.';

//     $total_principal = ($principal + $fees) * (1 + $tax_rate) * (1 + $fees_rate) + $rv_type_fees;

	$taxed_principal = $principal * (1 + $tax_rate);
 	$fees_amount = $principal * $fees_rate;
 	$total_principal = $taxed_principal + $fees_amount + $fees + $rv_type_fees;

	
    if ($total_principal <= 0) return 'Error: Total principal must be greater than zero.';

    error_log("Principal: $principal, Fees: $fees, Fees Rate: $fees_rate, Tax Rate: $tax_rate, Interest Rate: $interest_rate_per_period, RV Type Fees: $rv_type_fees");

    $loan_term_months = get_loan_term($model_year);
    $amortization_period_months = get_amortization_period($model_year);

    if (empty($loan_term_months) || empty($amortization_period_months)) {
        return 'Error: Invalid loan term or amortization period.';
    }

    $days_in_month = 30.4368;
    $days_in_period = 14;
    $total_days_loan = $amortization_period_months * $days_in_month;
    $total_periods_loan = ceil($total_days_loan / $days_in_period);

    error_log("Loan Term Months: $loan_term_months, Amortization Period Months: $amortization_period_months, Total Periods Loan: $total_periods_loan");

    $biweeklyPayment = $total_principal * $interest_rate_per_period / (1 - pow(1 + $interest_rate_per_period, -$total_periods_loan));

    if (!is_finite($biweeklyPayment) || $biweeklyPayment <= 0) {
        return 'Error: Invalid payment calculation.';
    }

    $total_payment = $biweeklyPayment * $total_periods_loan;

    $total_periods_term = floor($loan_term_months * $days_in_month / $days_in_period);
    $total_interest_term = 0;
    $remaining_principal = $total_principal;

    for ($i = 0; $i < $total_periods_term; $i++) {
        $interest_this_period = $remaining_principal * $interest_rate_per_period;
        $total_interest_term += $interest_this_period;
        $remaining_principal -= ($biweeklyPayment - $interest_this_period);

        if ($remaining_principal <= 0) {
            break;
        }
    }

    error_log("Biweekly Payment: $biweeklyPayment, Total Payment: $total_payment, Total Interest: $total_interest_term");

    $cost_of_borrowing = number_format($total_interest_term, 2, '.', '');

    return array(
        'payment_per_period' => ceil($biweeklyPayment),
        'total_payment' => ceil($total_payment),
        'cost_of_borrowing' => $cost_of_borrowing
    );
}
    
    
function payment_shortcode($atts) {
    // Prevent execution in Elementor editor or admin
    if (is_admin() && (defined('ELEMENTOR_VERSION') || wp_doing_ajax())) {
        return ''; // Return nothing to avoid breaking the page layout
    }

    // Get the current post ID
    $post_id = get_the_ID(); 

    // If no post ID, return an error
    if (!$post_id) {
        return 'Error: Post ID is not available or invalid.';
    }

    // Fetch matching meta keys between 'rvcpc_meta' table and the current post
    $matching_meta_keys = get_matching_meta_keys($post_id);

    // If no matching meta keys are found, return a default message
    if (empty($matching_meta_keys)) {
        return ''; // Return nothing to avoid breaking the page layout
    }

    // Initialize data array
    $data = [];
    foreach ($matching_meta_keys as $key) {
        $meta_value = get_post_meta($post_id, $key, true);
        $data[$key] = !empty($meta_value) ? floatval($meta_value) : 0; // Ensure each key has a default of 0 if empty
    }

    // Calculate the lowest price, filtering out non-positive values
    $lowest_price = min(array_filter($data, function($value) {
        return $value > 0; // Filter out any zero or negative values
    }));

    // If lowest price is invalid, return an error
    if ($lowest_price <= 0) {
        return 'N/A';
    }

    // Calculate the payment using the lowest price
    $payment_result = calculate_payment($lowest_price);

    // Check for the presence of attributes
    $atts = shortcode_atts(['data' => ''], $atts);
    
    if ($atts['data'] === 'cob') {
        // Return cost of borrowing details
        if (is_array($payment_result) && isset($payment_result['cost_of_borrowing'])) {
            $cost_of_borrowing = $payment_result['cost_of_borrowing'];
            $loan_term_months = get_loan_term(get_model_year());
            $amortization_period_months = get_amortization_period(get_model_year());

            // Format output for C.O.B.
            return sprintf(
                '%s APR for %d/%d months ($%s C.O.B.) O.A.C.',
                number_format(get_setting_value('interest_rate'), 2) . '%',
                $loan_term_months,
                $amortization_period_months,
                number_format($cost_of_borrowing, 2) // Ensuring proper formatting
            );
        }
        return 'Error: Unable to calculate cost of borrowing.';
    } else {
        // Return payment per period
        if (is_array($payment_result) && isset($payment_result['payment_per_period']) && $payment_result['payment_per_period'] != 0) {
            return esc_html($payment_result['payment_per_period']);
        }
        return 'Error: Unable to calculate payment or payment is zero.';
    }
}


add_shortcode('payment', 'payment_shortcode');

// Frontend Calculator Shortcode
// Frontend Calculator Shortcode
function calculator_shortcode() {
    // Prevent execution in Elementor editor or admin
    if (is_admin() && (defined('ELEMENTOR_VERSION') || wp_doing_ajax())) {
        return ''; // Return nothing to avoid breaking the page layout
    }

    // Get the current post ID
    $post_id = get_the_ID();

    // If no post ID, return an error
    if (!$post_id) {
        return 'Error: Post ID is not available or invalid.';
    }

    // Fetch matching meta keys and calculate the lowest price
    $matching_meta_keys = get_matching_meta_keys($post_id);
    $data = [];
    foreach ($matching_meta_keys as $key) {
        $meta_value = get_post_meta($post_id, $key, true);
        $data[$key] = !empty($meta_value) ? floatval($meta_value) : 0;
    }

    // Calculate the lowest price or set it to 0 if invalid
    $lowest_price = min(array_filter($data, function($value) {
        return $value > 0;
    }));

    if ($lowest_price <= 0) {
        $lowest_price = 0; // Default to 0 if no valid price is available
    }

    // Fetch the default APR from the plugin settings
    $default_apr = get_setting_value('interest_rate');

    // Generate the HTML for the calculator
    ob_start(); // Start output buffering
    ?>
    <div class="container calculator">
        <form id="calculator-form">
			<div class="left-column">
			
            <div class="form-group">
                <label for="rv-price">RV Price</label>
                <input type="number" class="form-control" id="rv-price" name="rv_price" placeholder="0" value="<?php echo esc_attr($lowest_price); ?>">
            </div>
            <div class="form-group">
                <label for="downpayment">Down Payment</label>
                <input type="number" class="form-control" id="downpayment" name="downpayment" value="0"> <!-- Default down payment is 0 -->
            </div>
            <div class="form-group">
                <label for="annual-rate">Annual Funding Rate (%)</label>
                <input type="number" class="form-control" id="annual-rate" name="annual_rate" step="0.01" placeholder="<?php echo esc_attr($default_apr); ?>" value="<?php echo esc_attr($default_apr); ?>">
            </div>
						</div>
			<div class="right-column">
			
            <div class="form-group">
                <label>Loan Term (Months)</label>
                <select class="form-control" name="loan_term" id="loan_term">
                    <?php for ($i = 12; $i <= 240; $i += 12): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i == 240) ? 'selected' : ''; ?>><?php echo $i; ?> Months</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Frequency</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payment_frequency" id="frequency-weekly" value="weekly" checked>
                        <label class="form-check-label" for="frequency-weekly">Weekly</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payment_frequency" id="frequency-biweekly" value="biweekly">
                        <label class="form-check-label" for="frequency-biweekly">Biweekly</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payment_frequency" id="frequency-monthly" value="monthly">
                        <label class="form-check-label" for="frequency-monthly">Monthly</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Estimated Payment:</label>
                <div class="result">$ <span id="payment-estimate">0.00</span></div>
            </div>
			
						</div>
        </form>
    </div>

    <script>
    // Function to calculate payment
    function calculatePayment() {
    var formData = new FormData(document.getElementById('calculator-form'));
    formData.append('action', 'calculate_payment_ajax');

    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log(data); // Log the response for debugging
        if (data.success) {
            // Format the payment with a comma as the thousand separator
            var formattedPayment = new Intl.NumberFormat('en-US', {
                style: 'decimal',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(data.data.payment || 0);

            // Update the payment estimate dynamically
            document.getElementById('payment-estimate').innerText = formattedPayment;
        } else {
            console.error('Error in calculation:', data);
        }
    })
    .catch(error => console.error('Error:', error));
}


    // Event listener function
    function addCalculatorEventListeners() {
        // Add event listeners to input fields and radio buttons for real-time updates
        document.querySelectorAll('#calculator-form input, #calculator-form select').forEach(input => {
            input.addEventListener('input', calculatePayment); // Trigger calculation on input change
        });

        document.querySelectorAll('#calculator-form input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', calculatePayment); // Trigger calculation on radio button change
        });
    }

    // Initialize the event listeners when the document is ready
    document.addEventListener('DOMContentLoaded', function() {
        addCalculatorEventListeners();
        calculatePayment(); // Trigger initial calculation on page load
    });
</script>

    <?php
    return ob_get_clean(); // Return the buffered content
}

add_shortcode('rv_financing_calculator', 'calculator_shortcode');

// AJAX handler to calculate the payment
function calculate_payment_ajax() {
    $price = floatval($_POST['rv_price']);
    $downpayment = floatval($_POST['downpayment']);
    $annual_rate = floatval($_POST['annual_rate']) / 100;
    $loan_term = intval($_POST['loan_term']);
    $payment_frequency = $_POST['payment_frequency'];

    // Ensure proper values
    if ($price <= 0 || $annual_rate <= 0 || $loan_term <= 0) {
        wp_send_json_error('Invalid input values');
    }

    // Fetch additional fees and tax rates
    $loan_fee = floatval(get_setting_value('loan_fee'));
    $tax_rate = floatval(get_setting_value('tax_rate')) / 100;
    $taxed_fee = floatval(get_setting_value('taxed_fee'));

    // Calculate the principal after downpayment and fees
    $principal = ($price - $downpayment + $loan_fee) * (1 + $tax_rate + $taxed_fee);

    // Determine the payment periods based on frequency
    $periods_per_year = 52; // Weekly by default
    if ($payment_frequency === 'biweekly') {
        $periods_per_year = 26;
    } else if ($payment_frequency === 'monthly') {
        $periods_per_year = 12;
    }


    // Calculate the interest rate per period
    $interest_rate_per_period = $annual_rate / $periods_per_year;

    // Total number of periods for the loan term
    $total_periods = $loan_term * ($periods_per_year / 12);

    // Calculate the payment using the amortization formula
    if ($interest_rate_per_period > 0) {
        $payment = $principal * $interest_rate_per_period / (1 - pow(1 + $interest_rate_per_period, -$total_periods));
    } else {
        // If interest rate is 0, divide the principal by total periods
        $payment = $principal / $total_periods;
    }

    // Ensure payment is a valid number
    if (is_nan($payment) || $payment <= 0) {
        wp_send_json_error('Error calculating payment');
    }

    // Return the payment result
    wp_send_json_success(array(
        'payment' => number_format($payment, 2, '.', '')
    ));
}

add_action('wp_ajax_calculate_payment_ajax', 'calculate_payment_ajax');
add_action('wp_ajax_nopriv_calculate_payment_ajax', 'calculate_payment_ajax');

