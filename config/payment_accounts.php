<?php
/**
 * Payment Account Configuration
 * Update these details with your actual payment account information
 */

// GCash Account
define('GCASH_ACCOUNT_NAME', 'MC Repair Services');
define('GCASH_NUMBER', '09123456789');

// PayMaya Account
define('PAYMAYA_ACCOUNT_NAME', 'MC Repair Services');
define('PAYMAYA_NUMBER', '09987654321');

// PayPal Account
define('PAYPAL_ACCOUNT_NAME', 'MC Repair Services');
define('PAYPAL_EMAIL', 'mcrepair@paypal.com');

// Bank Transfer Account
define('BANK_NAME', 'BDO');
define('BANK_ACCOUNT_NAME', 'MC Repair Services');
define('BANK_ACCOUNT_NUMBER', '1234-5678-9012');

/**
 * Get payment account details as JSON
 * This can be used by JavaScript to display payment information
 */
function get_payment_accounts_json() {
    return json_encode([
        'GCash' => [
            'name' => GCASH_ACCOUNT_NAME,
            'number' => GCASH_NUMBER,
            'instructions' => 'Send payment to the GCash number above and upload the screenshot.'
        ],
        'PayMaya' => [
            'name' => PAYMAYA_ACCOUNT_NAME,
            'number' => PAYMAYA_NUMBER,
            'instructions' => 'Send payment to the PayMaya number above and upload the screenshot.'
        ],
        'PayPal' => [
            'name' => PAYPAL_ACCOUNT_NAME,
            'email' => PAYPAL_EMAIL,
            'instructions' => 'Send payment to the PayPal email above and upload the screenshot.'
        ],
        'Bank Transfer' => [
            'bank' => BANK_NAME,
            'accountName' => BANK_ACCOUNT_NAME,
            'accountNumber' => BANK_ACCOUNT_NUMBER,
            'instructions' => 'Transfer to the bank account above and upload the deposit slip.'
        ]
    ]);
}
?>
