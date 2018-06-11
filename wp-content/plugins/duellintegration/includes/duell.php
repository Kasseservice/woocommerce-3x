<?php

if (!function_exists('write_log')) {

    function write_log($log, $excludeCheck = false) {
        $duellLogStatus = get_option('duellintegration_log_status');
        if (($duellLogStatus == '1' || $duellLogStatus == 1) || $excludeCheck == true) {

            $dirpath = WP_CONTENT_DIR . '/uploads/duell/';

            if (!file_exists($dirpath)) {
                mkdir($dirpath, 0755, true);
            }

            $logFileName = date('Y-m-d') . '.log';
            $prefix = "[" . date('Y-m-d H:i:s') . "]";

            error_log(PHP_EOL . PHP_EOL, 3, $dirpath . $logFileName);
            if (is_array($log) || is_object($log)) {
                error_log($prefix . " " . print_r($log, true), 3, $dirpath . $logFileName);
            } else {
                error_log($prefix . " " . $log, 3, $dirpath . $logFileName);
            }
        }
    }

}
if (!function_exists('validateDateTime')) {

    function validateDateTime($dateStr, $format) {
        date_default_timezone_set('UTC');
        $date = DateTime::createFromFormat($format, $dateStr);
        return $date && ($date->format($format) === $dateStr);
    }

}
if (!function_exists('getWooCommerceOrderProductsById')) {

    function getWooCommerceOrderProductsById($id, $fields = null, $filter = array()) {
        if (is_wp_error($id))
            return $id;
        // Get the decimal precession
        $dp = (isset($filter['dp'])) ? intval($filter['dp']) : 2;
        $_tax = new WC_Tax();
        $order = wc_get_order($id); //getting order Object
        $order_data = array(
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number()
        );
        //getting all line items
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $product_id = null;
            $product_sku = null;
            $item_tax_rate = 0;
            // Check if the product exists.
            if (is_object($product)) {
                $product_id = $product->get_id();
                $product_sku = $product->get_sku();
                $taxes = $_tax->get_rates($product->get_tax_class());
                $rates = array_shift($taxes);
                //Take only the item rate and round it.
                $item_tax_rate = round(array_shift($rates));
            }
            $order_data['line_items'][] = array(
                'id' => $item_id,
                'subtotal' => wc_format_decimal($order->get_line_subtotal($item, false, false), $dp),
                'subtotal_tax' => wc_format_decimal($item['line_subtotal_tax'], $dp),
                'total' => wc_format_decimal($order->get_line_total($item, false, false), $dp),
                'total_tax' => wc_format_decimal($item['line_tax'], $dp),
                'price' => wc_format_decimal($order->get_item_total($item, false, false), $dp),
                'item_tax_rate' => $item_tax_rate,
                'quantity' => wc_stock_amount($item['qty']),
                'tax_class' => (!empty($item['tax_class']) ) ? $item['tax_class'] : null,
                'name' => $item['name'],
                'product_id' => (!empty($item->get_variation_id()) && ('product_variation' === $product->post_type )) ? $product->get_parent_id() : $product_id,
                'variation_id' => (!empty($item->get_variation_id()) && ('product_variation' === $product->post_type )) ? $product_id : 0,
                'product_url' => get_permalink($product_id),
                'product_thumbnail_url' => wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'thumbnail', TRUE)[0],
                'sku' => $product_sku,
                'meta' => wc_display_item_meta($item)
            );
        }
        return array('order' => apply_filters('woocommerce_api_order_response', $order_data, $order, $fields));
    }

}
if (!function_exists('getWooCommerceOrderDetailById')) {

    function getWooCommerceOrderDetailById($id, $fields = null, $filter = array()) {
        if (is_wp_error($id))
            return $id;
        // Get the decimal precession
        $dp = (isset($filter['dp'])) ? intval($filter['dp']) : 2;
        $_tax = new WC_Tax();
        $order = wc_get_order($id); //getting order Object
        $duell_customer_id = get_post_meta($order->get_id(), '_duell_customer_id', true);
        $order_data = array(
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'created_at' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'updated_at' => $order->get_date_modified()->date('Y-m-d H:i:s'),
            'completed_at' => !empty($order->get_date_completed()) ? $order->get_date_completed()->date('Y-m-d H:i:s') : '',
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
            'total' => wc_format_decimal($order->get_total(), $dp),
            'subtotal' => wc_format_decimal($order->get_subtotal(), $dp),
            'total_line_items_quantity' => $order->get_item_count(),
            'total_tax' => wc_format_decimal($order->get_total_tax(), $dp),
            'total_shipping' => wc_format_decimal($order->get_total_shipping(), $dp),
            'cart_tax' => wc_format_decimal($order->get_cart_tax(), $dp),
            'shipping_tax' => wc_format_decimal($order->get_shipping_tax(), $dp),
            'total_discount' => wc_format_decimal($order->get_total_discount(), $dp),
            'shipping_methods' => $order->get_shipping_method(),
            'order_key' => $order->get_order_key(),
            'payment_details' => array(
                'method_id' => $order->get_payment_method(),
                'method_title' => $order->get_payment_method_title(),
                'paid_at' => !empty($order->get_date_paid()) ? $order->get_date_paid()->date('Y-m-d H:i:s') : '',
            ),
            'billing_address' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'company' => $order->get_billing_company(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'formated_state' => (!empty($order->get_billing_country()) && !empty($order->get_billing_state())) ? WC()->countries->states[$order->get_billing_country()][$order->get_billing_state()] : '', //human readable formated state name
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
                'formated_country' => !empty($order->get_billing_country()) ? WC()->countries->countries[$order->get_billing_country()] : '', //human readable formated country name
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'duell_customer_id' => !is_null($duell_customer_id) && (int) $duell_customer_id > 0 ? (int) $duell_customer_id : 0
            ),
            'shipping_address' => array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'formated_state' => (!empty($order->get_shipping_country()) && !empty($order->get_shipping_state())) ? WC()->countries->states[$order->get_shipping_country()][$order->get_shipping_state()] : '', //human readable formated state name
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
                'formated_country' => !empty($order->get_shipping_country()) ? WC()->countries->countries[$order->get_shipping_country()] : '' //human readable formated country name
            ),
            'note' => $order->get_customer_note(),
            'customer_ip' => $order->get_customer_ip_address(),
            'customer_user_agent' => $order->get_customer_user_agent(),
            'customer_id' => $order->get_user_id(),
            'view_order_url' => $order->get_view_order_url(),
            'line_items' => array(),
            'shipping_lines' => array(),
            'tax_lines' => array(),
            'fee_lines' => array(),
            'coupon_lines' => array(),
        );
//getting all line items
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $product_id = null;
            $product_sku = null;
            $product_category = null;
            $category_id = 0;
            $category_name = null;
            $duell_category_id = 0;
            $item_tax_rate = 0;
// Check if the product exists.
            if (is_object($product)) {
                $product_id = $product->get_id();
                $product_sku = $product->get_sku();
                $terms = get_the_terms($product_id, 'product_cat');
                if (isset($terms[0])) {
                    $category_id = $terms[0]->term_id;
                    $category_name = $terms[0]->name;
                    $duell_category_id = get_term_meta($category_id, '_duell_category_id', true);
                }
                $taxes = $_tax->get_rates($product->get_tax_class());
                $rates = array_shift($taxes);
                //Take only the item rate and round it.
                $item_tax_rate = round(array_shift($rates));
            }
            $order_data['line_items'][] = array(
                'id' => $item_id,
                'subtotal' => wc_format_decimal($order->get_line_subtotal($item, false, false), $dp),
                'subtotal_tax' => wc_format_decimal($item['line_subtotal_tax'], $dp),
                'total' => wc_format_decimal($order->get_line_total($item, false, false), $dp),
                'total_tax' => wc_format_decimal($item['line_tax'], $dp),
                'item_tax_rate' => $item_tax_rate,
                'price' => wc_format_decimal($order->get_item_total($item, false, false), $dp),
                'quantity' => wc_stock_amount($item['qty']),
                'tax_class' => (!empty($item['tax_class']) ) ? $item['tax_class'] : null,
                'name' => $item['name'],
                'product_id' => (!empty($item->get_variation_id()) && ('product_variation' === $product->post_type )) ? $product->get_parent_id() : $product_id,
                'variation_id' => (!empty($item->get_variation_id()) && ('product_variation' === $product->post_type )) ? $product_id : 0,
                'product_url' => get_permalink($product_id),
                'product_thumbnail_url' => wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'thumbnail', TRUE)[0],
                'sku' => $product_sku,
                'meta' => wc_display_item_meta($item),
                'duell_product_id' => !is_null($product_id) ? get_post_meta($product_id, '_duell_product_id', true) : 0,
                'category_id' => $category_id,
                'category_name' => $category_name,
                'duell_category_id' => !is_null($duell_category_id) ? $duell_category_id : 0,
            );
        }
//getting shipping
        foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
            $order_data['shipping_lines'][] = array(
                'id' => $shipping_item_id,
                'method_id' => $shipping_item['method_id'],
                'method_title' => $shipping_item['name'],
                'total' => wc_format_decimal($shipping_item['cost'], $dp),
            );
        }
//getting taxes
        foreach ($order->get_tax_totals() as $tax_code => $tax) {
            $order_data['tax_lines'][] = array(
                'id' => $tax->id,
                'rate_id' => $tax->rate_id,
                'code' => $tax_code,
                'title' => $tax->label,
                'total' => wc_format_decimal($tax->amount, $dp),
                'compound' => (bool) $tax->is_compound,
            );
        }
//getting fees
        foreach ($order->get_fees() as $fee_item_id => $fee_item) {
            $order_data['fee_lines'][] = array(
                'id' => $fee_item_id,
                'title' => $fee_item['name'],
                'tax_class' => (!empty($fee_item['tax_class']) ) ? $fee_item['tax_class'] : null,
                'total' => wc_format_decimal($order->get_line_total($fee_item), $dp),
                'total_tax' => wc_format_decimal($order->get_line_tax($fee_item), $dp),
            );
        }
//getting coupons
        foreach ($order->get_items('coupon') as $coupon_item_id => $coupon_item) {
            $order_data['coupon_lines'][] = array(
                'id' => $coupon_item_id,
                'code' => $coupon_item['name'],
                'amount' => wc_format_decimal($coupon_item['discount_amount'], $dp),
            );
        }
        return array('order' => apply_filters('woocommerce_api_order_response', $order_data, $order, $fields));
    }

}
if (!function_exists('getWooCommerceProductBySku')) {

    function getWooCommerceProductBySku($sku) {
        global $wpdb;
        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));
        if ($product_id) {
            return $product_id;
        }
        return null;
    }

}
if (!function_exists('duellLoginApi')) {

    function duellLoginApi($action, $method = 'POST', $data = array(), $content_type = 'json', $type = 'manual') {
        try {
            $method = strtoupper($method);
            write_log('loginApi(' . $action . ') - Data: ' . json_encode($data));
            $url = DUELL_API_ENDPOINT . $action;
            $headers = array();
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $curl = curl_init();
            switch ($method) {
                case "POST":
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                    if (!empty($data)) {
                        curl_setopt($curl, CURLOPT_POST, count($data));
                        $data = http_build_query($data);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    }
                    break;
                case "PUT":
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($curl, CURLOPT_PUT, 1);
                    if (!empty($data)) {
                        $url = sprintf("%s?%s", $url, http_build_query($data));
                    }
                    break;
                default:
                    if (!empty($data)) {
                        $url = sprintf("%s?%s", $url, http_build_query($data));
                    }
            }
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_USERAGENT, "Duell Integration WP");
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($curl);
//write_log('loginApi() - Result of : "' . $result . '"');
            if (!$result) {
                $text_error = 'loginApi() - Curl Failed ' . curl_error($curl);
                write_log($text_error . ' ' . curl_errno($curl));
                if ($type != 'manual') {
                    duellMailAlert($text_error, curl_errno($curl));
                }
            }
            curl_close($curl);
            if ($content_type == 'json') {
                $encoding = mb_detect_encoding($result);
                if ($encoding == 'UTF-8') {
                    $result = preg_replace('/[^(\x20-\x7F)]*/', '', $result);
                }
                $res = json_decode($result, true);
                if (empty($res)) {
                    $res['code'] = 100010;
                    $res['status'] = FALSE;
                    $res['token'] = '';
                    $res['message'] = 'Webservice is temporary unavailable. Please try again.';
                    write_log('loginApi() - Result json_decode is not proper');
                } else {
                    if ($res['status'] === true) {

                    } else {
                        $result_code = '';
                        if (isset($res['code']) && $res['code'] != '') {
                            $result_code = $res['code'];
                        }
                        $result_message = '';
                        if (isset($res['message']) && $res['message'] != '') {
                            $result_message = $res['message'];
                        }
                        $text_error = 'loginApi() - Result Failed - ' . $result_message;
                        write_log('loginApi() - Result Failed ' . $result_code . ' ' . $result_message);
                        if ($type != 'manual') {
                            //duellMailAlert($text_error, $result_code);
                        }
                    }
                }
            }
        } catch (Error $e) {
            $res['code'] = 100010;
            $res['status'] = FALSE;
            $res['token'] = '';
            $res['message'] = $e->getMessage();
            $text_error = 'loginApi() - Error exception throw:: ' . $e->getMessage();
            write_log($text_error);
            if ($type != 'manual') {
                duellMailAlert($text_error, 422);
            }
        } catch (Exception $e) {
            $res['code'] = 100010;
            $res['status'] = FALSE;
            $res['token'] = '';
            $res['message'] = $e->getMessage();
            $text_error = 'loginApi() - Catch exception throw:: ' . $e->getMessage();
            write_log($text_error);
            if ($type != 'manual') {
                duellMailAlert($text_error, 422);
            }
        }
        return $res;
    }

}
if (!function_exists('callDuell')) {

    function callDuell($action, $method = 'POST', $data = array(), $content_type = 'json', $type = 'manual') {
        try {
            $requestedData = $data;
            $method = strtoupper($method);
            write_log('call(' . $action . ') - Data: ' . json_encode($data));
            $url = DUELL_API_ENDPOINT . $action;
            $token = '';
            if (get_option('duellintegration_api_access_token') != '') {
                $token = get_option('duellintegration_api_access_token');
            } else if (isset($_COOKIE[DUELL_KEY_NAME]) && !empty($_COOKIE[DUELL_KEY_NAME])) {
                $token = $_COOKIE[DUELL_KEY_NAME];
                update_option('duellintegration_api_access_token', $token);
            } else {
                $loginAttempt = 1;
                while ($loginAttempt <= DUELL_TOTAL_LOGIN_ATTEMPT) {
                    //write_log('call(' . $action . ') - login Attempt: ' . $loginAttempt);
                    $tokenData = duellLoginApi(DUELL_LOGIN_ACTION, 'POST', $requestedData, $content_type, $type);
                    if ($tokenData['status'] == true) {
                        //==save in session or cookie
                        $token = $tokenData['token'];
                        if ($token != '') {
                            setcookie(DUELL_KEY_NAME, $token, time() + (86400 * 30), "/"); // 86400 = 1 day
                            update_option('duellintegration_api_access_token', $token);
                            break;
                        }
                    }
                    $loginAttempt++;
                }
            }
            if ($token == '') {
                $res['code'] = 100010;
                $res['status'] = FALSE;
                $text_error = 'Not able to login with given crediential. Please check your settings.';
                $res['message'] = $text_error;
                write_log('call() - ' . $text_error);
                if ($type != 'manual') {
                    duellMailAlert('call() - ' . $text_error, 100010);
                }
                return $res;
            }
            /* For testing purpose
              if (DUELL_CNT == 0) {
              $token = "";
              DUELL_CNT++;
              } */
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $headers[] = 'Authorization: Bearer ' . $token;
            $curl = curl_init();
            switch ($method) {
                case "POST":
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                    if (!empty($data)) {
                        curl_setopt($curl, CURLOPT_POST, count($data));
                        $data = json_encode($data);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    }
                    break;
                case "PUT":
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($curl, CURLOPT_PUT, 1);
//$data = json_encode($data);
//curl_setopt($curl, CURLOPT_POSTFIELDS,http_build_query($data));
                    if (!empty($data)) {
                        $url = sprintf("%s?%s", $url, http_build_query($data));
                    }
                    break;
                default:
                    if (!empty($data)) {
                        $url = sprintf("%s?%s", $url, http_build_query($data));
                    }
            }
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_USERAGENT, "Duell Integration WP");
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($curl);
            write_log('call() - Result of : "' . $result . '"');
            if (!$result) {
                $text_error = 'call() - Curl Failed ' . curl_error($curl);
                write_log($text_error . ' ' . curl_errno($curl));
                if ($type != 'manual') {
                    duellMailAlert($text_error, curl_errno($curl));
                }
            }
            curl_close($curl);
            if ($content_type == 'json') {
                $encoding = mb_detect_encoding($result);
                if ($encoding == 'UTF-8') {
                    $result = preg_replace('/[^(\x20-\x7F)]*/', '', $result);
                }
                $res = json_decode($result, true);
                if (empty($res)) {
                    $res['code'] = 100010;
                    $res['status'] = FALSE;
                    $res['message'] = 'Webservice is temporary unavailable. Please try again.';
                    write_log('call() - Result json_decode is not proper');
                } else {
                    if ($res['status'] === true) {

                    } else {
                        $result_code = '';
                        if (isset($res['code']) && $res['code'] != '') {
                            $result_code = $res['code'];
                        }
                        $result_message = '';
                        if (isset($res['message']) && $res['message'] != '') {
                            $result_message = $res['message'];
                        }
                        write_log('call() - Result Failed ' . $result_code . ' ' . $result_message);
                        if ((int) $result_code == 401 || (int) $result_code == 403) {
//==relogin
                            unset($_COOKIE[DUELL_KEY_NAME]);
                            update_option('duellintegration_api_access_token', '');
                            return callDuell($action, $method, $requestedData, $content_type, $type);
                        } else {
                            if ($type != 'manual') {
                                duellMailAlert('call(' . $action . ') - ' . $result_message, $result_code);
                            }
                        }
                    }
                }
            }
        } catch (Error $e) {
            $res['code'] = 100010;
            $res['status'] = FALSE;
            $res['message'] = $e->getMessage();
            write_log('call() - Error exception throw:: ' . $e->getMessage());
            if ($type != 'manual') {
                duellMailAlert('call(' . $action . ') - Error exception throw:: ' . $e->getMessage(), 100010);
            }
        } catch (Exception $e) {
            $res['code'] = 100010;
            $res['status'] = FALSE;
            $res['message'] = $e->getMessage();
            write_log('call() - Catch exception throw:: ' . $e->getMessage());
            if ($type != 'manual') {
                duellMailAlert('call(' . $action . ') - Catch exception throw::  ' . $e->getMessage(), 100010);
            }
        }
        return $res;
    }

}
if (!function_exists('validateJsonDecode')) {

    function validateJsonDecode($data) {
        $data = (string) $data;
        $encoding = mb_detect_encoding($data);
        if ($encoding == 'UTF-8') {
            $data = preg_replace('/[^(\x20-\x7F)]*/', '', $data);
            $data = preg_replace('#\\\\x[0-9a-fA-F]{2,2}#', '', $data);
        }
        $data = json_decode($data);
        if (function_exists('json_last_error')) {
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    write_log('validateJsonDecode() - No json decode errors');
                    break;
                case JSON_ERROR_DEPTH:
                    write_log('validateJsonDecode() - Maximum stack depth exceeded');
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    write_log('validateJsonDecode() - Underflow or the modes mismatch');
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    write_log('validateJsonDecode() - Unexpected control character found');
                    break;
                case JSON_ERROR_SYNTAX:
                    write_log('validateJsonDecode() - Syntax error, malformed JSON');
                    break;
                case JSON_ERROR_UTF8:
                    write_log('validateJsonDecode() - Malformed UTF-8 characters, possibly incorrectly encoded');
                    break;
                default:
                    write_log('validateJsonDecode() - Unknown error');
                    break;
            }
        } else {
            write_log('validateJsonDecode() - json_last_error PHP function does not exist');
        }
        return $data;
    }

}
if (!function_exists('duellMailAlert')) {

    function duellMailAlert($error_message = '', $error_code = '') {
        //implement mail functions which sends email to site admin from option table   get_option('admin_email')
    }

}