<?php

session_start();

$servername = "localhost";  // DB host
$username   = "*****";      // DB username
$password   = "*****";      // DB password
$dbname     = "*******";    // DB name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// IPN start

define( 'IPN_PASSPHRASE', '' );

function digistore_signature( $sha_passphrase, $parameters, $convert_keys_to_uppercase = false, $do_html_decode=false )
{
    $algorythm           = 'sha512';
    $sort_case_sensitive = true;

    if (!$sha_passphrase)
    {
        return 'no_signature_passphrase_provided';
    }

    unset( $parameters[ 'sha_sign' ] );
    unset( $parameters[ 'SHASIGN' ] );

    if ($convert_keys_to_uppercase)
    {
        $sort_case_sensitive = false;
    }

    $keys = array_keys($parameters);
    $keys_to_sort = array();
    foreach ($keys as $key)
    {
        $keys_to_sort[] = $sort_case_sensitive
            ? $key
            : strtoupper( $key );
    }

    array_multisort( $keys_to_sort, SORT_STRING, $keys );

    $sha_string = "";
    foreach ($keys as $key)
    {
        $value = $parameters[$key];

        if ($do_html_decode) {
            $value = html_entity_decode( $value );
        }

        $is_empty = !isset($value) || $value === "" || $value === false;
        if ($is_empty)
        {
            continue;
        }

        $upperkey = $convert_keys_to_uppercase
            ? strtoupper( $key )
            : $key;

        $sha_string .= "$upperkey=$value$sha_passphrase";
    }

    $sha_sign = strtoupper( hash( $algorythm, $sha_string) );

    return $sha_sign;
}

function posted_value($varname)
{
    return empty($_POST[ $varname ]) ? '' : $_POST[ $varname ];
}


$event    = posted_value('event');
$api_mode = posted_value('api_mode'); // 'live' or 'test'

$ipn_data = $_POST;

$must_validate_signature = IPN_PASSPHRASE != '';
if ($must_validate_signature)
{
    $received_signature = posted_value('sha_sign');
    $expected_signature = digistore_signature( IPN_PASSPHRASE, $ipn_data);

    $sha_sign_valid = $received_signature == $expected_signature;

    if (!$sha_sign_valid)
    {

        die('ERROR: invalid sha signature');

    }
}


switch ($event)
{
    case 'connection_test':
    {
        die('OK');
    }

    case 'on_payment':
    {
        $order_id = posted_value('order_id');
        $product_id   = posted_value('product_id');
        $product_name = posted_value('product_name');
        $billing_type = posted_value( 'billing_type' );

        switch ($billing_type)
        {
            case 'single_payment':
                $number_payments = 0;
                $pay_sequence_no = 0;
                break;

            case 'installment':
                $number_payments = posted_value( 'order_item_number_of_installments' );
                $pay_sequence_no = posted_value( 'pay_sequence_no' );
                break;

            case 'subscription':
                $number_payments = 0;
                $pay_sequence_no = posted_value( 'pay_sequence_no' );
                break;
        }


        $email             = posted_value('email');
        $fname             = posted_value('address_first_name');
        $lname             = posted_value('address_last_name');
        $address_street    = posted_value('address_street_name');
        $address_street_no = posted_value('address_street_number');
        $address_city      = posted_value('address_city');
        $address_state     = posted_value('address_state');
        $address_zipcode   = posted_value('address_zipcode');
        $address_phone_no  = posted_value('address_phone_no');


        $is_test_mode = $api_mode != 'live';


        $do_transfer_member_ship_data_to_digistore = false; // if true, membership access data (or other data) may be displayed on the order confirmation email, receipt page and so on

        if (!$do_transfer_member_ship_data_to_digistore)
        {

            /********** Thats the main area where you need to write code  *******************/

            $transaction_id = posted_value('transaction_id');
            $txnType = posted_value('transaction_type');
            $phone = posted_value('address_phone_no');
            $city = posted_value('buyer_address_city');
            $county = posted_value('buyer_address_country');
            $state = posted_value('buyer_address_state');
            $postalCode = posted_value('buyer_address_zipcode');
            $country = posted_value('buyer_address_country');
            $affiliatePayout = "";
            $recurring = "";
            $accountAmount = posted_value('amount');
            $downloadUrl = posted_value('receipt_url');
            $upsellOriginalReceipt      = posted_value('upsell_no');
            $upsellSession              = posted_value('upsell_no');
            $upsellPath                 = posted_value('upsell_path');

            $last_id = 1;

            /* ******************************   Example of insert row query  ************************* */

            $sql = "INSERT INTO test_table (user_id, txn_id, phoneNumber, city, county, state, postalCode, country, transactionType, ... ) VALUES ('$last_id','$transaction_id','$phone','$city','$county','$state','$postalCode','$country','$txnType', ... )";
            if ($conn->query($sql) === TRUE) {
            $last_id = $conn->insert_id;
            } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            }


            /* ******************************   Example of update row query  ************************* */

            $first_name = 'Emon';
            $last_name = 'Ahmed';

            $update_sql_query = "UPDATE test_table SET first_name='$first_name', last_name='$last_name' WHERE id='$last_id'";
            if ($conn->query($update_sql_query) === TRUE) {
            echo "Record updated successfully";
            } else {
            echo "Error: " . $update_sql_query . "<br>" . $conn->error;
            }



            /* ******************************   Example of delete row query  ************************* */

            $delete_sql_query = "DELETE FROM test_table WHERE id='$last_id'";
            if ($conn->query($delete_sql_query) === TRUE) {
            echo "Record deleted";
            } else {
                echo "Error: " . $delete_sql_query . "<br>" . $conn->error;
            }

            die('OK');
        }
        else
        {
                $username     = 'some_username';
                $password     = 'some_password';
                $login_url    = 'http://domain.com/login';
                $thankyou_url = 'http://domain.com/thank_you';

                $show_on = 'all';     // e.g.: 'all',  'invoice', 'invoice,receipt_page,order_confirmation_email' - seperate multiple targets by comma
                $hide_on = 'invoice'; // e.g.: 'none', 'invoice', 'invoice,receipt_page,order_confirmation_email' - seperate multiple targets by comma

                $headline = 'Your access data'; // displayed above the membership access data
                $is_test_mode = $api_mode != 'live';
                // Write query for not membership from digistore
                die( "OK
                    thankyou_url: $thankyou_url
                    username: $username
                    password: $password
                    loginurl: $login_url
                    headline: $headline
                    show_on: $show_on
                    hide_on: $hide_on" 
                );
        }
    }

    case 'on_payment_missed':
    {
        $order_id = posted_value('order_id');
        $is_test_mode = $api_mode != 'live';
        // Write query for on_payment_missed
        die('OK');
    }

    case 'on_refund':
    {
        $order_id = posted_value('order_id');
        $is_test_mode = $api_mode != 'live';
        // Write query for on_refund
        die('OK');
    }

    case 'on_chargeback':
    {
        $order_id = posted_value('order_id');
        $is_test_mode = $api_mode != 'live';
        // Write query for on_chargeback
        die('OK');
    }

    case 'on_rebill_resumed':
    {
        $order_id = posted_value('order_id');
        $is_test_mode = $api_mode != 'live';
        // Write query for rebill
        die('OK');
    }

    case 'on_rebill_cancelled':
    {
        $order_id = posted_value('order_id');
        $is_test_mode = $api_mode != 'live';        
        $email = posted_value('email');
        // Write query for cancel
        die('OK');
    }

    case 'on_affiliation':
    {
        $email             = posted_value('email');
        $digistore_id      = posted_value('affiliate_name');
        $promolink         = posted_value('affiliate_link');
        $language          = posted_value('language');
        $first_name        = posted_value('address_first_name');
        $last_name         = posted_value('address_last_name');
        $address_street    = posted_value('address_street_name');
        $address_street_no = posted_value('address_street_number');
        $address_city      = posted_value('address_city');
        $address_state     = posted_value('address_state');
        $address_zipcode   = posted_value('address_zipcode');
        $address_phone_no  = posted_value('address_phone_no');
        $product_id        = posted_value('product_id');
        $product_name      = posted_value('product_name');
        $merchant_id       = posted_value('merchant_id');
        $is_test_mode = $api_mode != 'live';
        // Write query for on_affiliation
        die('OK');
    }

    default:
    {
        // Unknown event
        die('OK');
    }
}

// IPN end
$conn->close();