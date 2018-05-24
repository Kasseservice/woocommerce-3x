<?php
defined('ABSPATH') or die('No script kiddies please!');
/*
  Plugin Name: Duell Integration
  Plugin URI: https://kasseservice.no/
  Description: Duell integration used to sync orders, products, customer with Duell.
  Author: kasseservice
  Version: 1.0
  Author URI: https://kasseservice.no/
 */


include( plugin_dir_path(__FILE__) . 'includes/duell.php');

class Duellintegration {

    public function __construct() {


        register_activation_hook(__FILE__, array($this, 'setup_install'));
        register_deactivation_hook(__FILE__, array($this, 'setup_uninstall'));

        add_action('plugins_loaded', array($this, 'plugin_init_setup'));



        // Hook into the admin menu
        add_action('admin_menu', array($this, 'create_plugin_settings_page'));

        // Add Settings and Fields
        add_action('admin_init', array($this, 'setup_sections'));
        add_action('admin_init', array($this, 'setup_fields'));


        add_action('admin_notices', array($this, 'update_notice'));
        add_action('admin_notices', array($this, 'error_notice'));


        add_filter('cron_schedules', array($this, 'cron_intervals_schedule'));
        add_action('duell_cron_sync_products', array($this, 'sync_products'));
        add_action('duell_cron_sync_prices', array($this, 'sync_prices'));
        add_action('duell_cron_sync_stocks', array($this, 'sync_stocks'));
        add_action('duell_cron_sync_orders', array($this, 'sync_orders'));

        //admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts_and_styles'));
        add_action('admin_footer', array($this, 'setup_action_javascript'));
        add_action('wp_ajax_manual_run_cron_action', array($this, 'manual_run_custom_cron'));

        add_action('woocommerce_thankyou', array($this, 'wc_subtract_stock_after_order_placed'), 111, 1);
        add_action('woocommerce_process_shop_order_meta', array($this, 'wc_subtract_stock_after_order_placed'), 10, 2);
    }

    function wc_subtract_stock_after_order_placed($order_id) {

        if (!$order_id) {
            return;
        }

        $order_detail = getOrderDetailById($order_id); //to get the detail of order ID #101
        //print_r($order_detail);
        write_log($order_detail);
        die;
    }

    function manual_run_custom_cron() {

        $reponse = array();
        if (!empty($_POST['param'])) {

            $cronName = strtolower($_POST['param']);
            do_action('duell_cron_' . $cronName);
            $response['response'] = "Added in background " . $cronName;
        } else {
            $response['response'] = "You didn't send the param";
        }


        header("Content-Type: application/json");
        echo json_encode($response);

        exit();
    }

    function plugin_init_setup() {
        $this->check_plugin_dependencies();
    }

    /**
     * Check dependencies.
     *
     * @throws Exception
     */
    function check_plugin_dependencies() {
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action('admin_notices', array($this, 'wc_dependency_warning_notice'));
            return false;
        }

        if (!function_exists('curl_init')) {
            add_action('admin_notices', array($this, 'curl_dependency_warning_notice'));
            return false;
        }

        return true;
    }

    function wc_dependency_warning_notice() {
        echo '<div class="error"><p><strong>' . sprintf(esc_html__('Duell integration requires WooCommerce to be installed and active. You can download %s here.', 'duellintegration'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
    }

    function curl_dependency_warning_notice() {
        echo '<div class="error"><p><strong>' . sprintf(esc_html__('Duell integration requires cURL to be installed on your server', 'duellintegration')) . '</strong></p></div>';
    }

    /*
     * Actions perform on activation of plugin
     */

    function setup_install() {

        global $wpdb;
        $table = $wpdb->prefix . "duell_sync_logs";
        $structure = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        type VARCHAR(80) NOT NULL,
        type_id bigint(20) NOT NULL,
        duell_ref bigint(20) NOT NULL,
        created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
        );";
        $wpdb->query($structure);

        if (!wp_next_scheduled('duell_cron_sync_products')) {
            wp_schedule_event(time(), 'every3hours', 'duell_cron_sync_products');
        }

        if (!wp_next_scheduled('duell_cron_sync_prices')) {
            wp_schedule_event(time(), 'every30minutes', 'duell_cron_sync_prices');
        }
        if (!wp_next_scheduled('duell_cron_sync_stocks')) {
            wp_schedule_event(time(), 'every30minutes', 'duell_cron_sync_stocks');
        }

        if (!wp_next_scheduled('duell_cron_sync_orders')) {
            //$next3am = ( date('Hi') >= '0300' ) ? strtotime('+1day 3am') : strtotime('3am');
            //wp_schedule_single_event($next3am, 'duell_cron_sync_orders');
            wp_schedule_event(strtotime('03:00:00'), 'daily3am', 'duell_cron_sync_orders');
        }
    }

    function cron_intervals_schedule($schedules) {
        $schedules['every3hours'] = array(
            'interval' => 10800,
            'display' => __('Every 3 hours')
        );
        $schedules['every30minutes'] = array(
            'interval' => 1800,
            'display' => __('Every 30 Minutes')
        );
        $schedules['daily3am'] = array(
            'interval' => 86400,
            'display' => __('Every day 3am')
        );

        return $schedules;
    }

    /*
     * Actions perform on de-activation of plugin
     */

    function setup_uninstall() {
        global $wpdb;

//deactivate cron jobs
        wp_clear_scheduled_hook('duell_cron_sync_products');
        wp_clear_scheduled_hook('duell_cron_sync_prices');
        wp_clear_scheduled_hook('duell_cron_sync_stocks');
        wp_clear_scheduled_hook('duell_cron_sync_orders');


        $table = $wpdb->prefix . "duell_sync_logs";

// drop a table
        $wpdb->query("DROP TABLE IF EXISTS $table");

// for site options in Multisite
        delete_option('duellintegration_client_number');
        delete_option('duellintegration_client_token');
        delete_option('duellintegration_stock_department_token');
        delete_option('duellintegration_order_department_token');
        delete_option('duellintegration_api_access_token');
        delete_option('duellintegration_log_status');
    }

    public function sync_products() {
        update_option('duellintegration_client_number', mt_rand(100000, 999999));
    }

    public function sync_prices() {
        update_option('duellintegration_client_token', date('Y-m-d H:i:s'));
    }

    public function sync_stocks() {
        update_option('duellintegration_stock_department_token', date('Y-m-d H:i:s'));
    }

    public function sync_orders() {
        $this->wc_subtract_stock_after_order_placed(16);
        //update_option('duellintegration_order_department_token', date('Y-m-d H:i:s'));
    }

    function create_plugin_settings_page() {
        /* http://clivern.com/adding-menus-and-submenus-for-wordpress-plugins/
         * Page title  used in the title tag of the page (shown in the browser bar) when it is displayed.
          Menu title  used in the menu on the left.
          Capability  the user level allowed to access the page.
          Menu slug  the slug used for the page in the URL.
          Function  the name of the function you will be using to output the content of the page.
          Icon  A url to an image or a Dashicons string.
          Position  The position of your item within the whole menu.
         */

        $capability = 'manage_options';

// Add the menu item and page
        $page_title = 'Duell Integration Settings';
        $menu_title = 'Duell Integration';
        $slug = 'duell-settings';
        $callback = array($this, 'plugin_settings_page_content');
        $icon = plugins_url() . '/duellintegration/assets/images/duell-icon.png';
        $position = 100;

        add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);


        /*
          parent_slug: Slug of the parent menu item.
          page_title: The page title.
          menu_title: The submenu title displayed on dashboard.
          capability: Minimum capability to view the submenu.
          menu_slug: Unique name used as a slug for submenu item.
          function: A callback function used to display page content.
         */
        add_submenu_page($slug, 'Settings', 'Settings', 'administrator', $slug, $callback);

        $log_page_title = 'Logs';
        $log_menu_title = 'Logs';
        $log_slug = 'duell-integration-logs';
        $log_callback = array($this, 'plugin_settings_page_content');

        add_submenu_page($slug, $log_page_title, $log_menu_title, $capability, $log_slug, $log_callback);
    }

    public function update_notice() {

// add error/update messages
// check if the user have submitted the settings
// wordpress will add the "settings-updated" $_GET parameter to the url
        if (isset($_GET['settings-updated'])) {
            // add settings saved message with the class of "updated"
            add_settings_error('duellintegration_messages', 'duellintegration_message', __('Settings Saved', 'duellintegration'), 'updated');
        }
    }

    public function error_notice() {
// show error/update messages
        settings_errors('duellintegration_messages');
    }

    public function plugin_settings_page_content() {

// check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>

        <div class="wrap">
          <h1><?php echo __('Duell Integration', 'duellintegration') ?></h1>


          <div class="col-left">

            <form method="post" action="options.php">
              <?php wp_nonce_field('duellintegration_nonce', 'duellintegration_nonce_field'); ?>
              <?php settings_fields('duellintegration'); ?>
              <?php do_settings_sections('duellintegration'); ?>
              <?php submit_button(); ?>

            </form>
          </div>


          <div class="col-right borderL">

            <div id="manual-cron-output" style="margin: 8px 0px;"></div>

            <div class="col-right-hf">


              <div class="infodiv">
                <h3><?php echo __('Sync Products', 'duellintegration') ?></h3>
                <p><?php echo __('Manual sync products with Duell', 'duellintegration') ?></p>
                <a href="javascript:void(0)" data-type="sync_products"  class="syncbutton manual-cron"><?php echo __('Run now', 'duellintegration') ?></a>

              </div>

              <div class="infodiv">
                <h3><?php echo __('Sync Stocks', 'duellintegration') ?></h3>
                <p><?php echo __('Manual sync stocks with Duell', 'duellintegration') ?></p>
                <a href="javascript:void(0)" data-type="sync_stocks" class="syncbutton manual-cron"><?php echo __('Run now', 'duellintegration') ?></a>
              </div>

            </div>

            <div class="col-right-hf borderL">

              <div class="infodiv">
                <h3><?php echo __('Sync Price', 'duellintegration') ?></h3>
                <p><?php echo __('Manual sync price with Duell', 'duellintegration') ?></p>
                <a href="javascript:void(0)" data-type="sync_prices" class="syncbutton manual-cron"><?php echo __('Run now', 'duellintegration') ?></a>
              </div>

              <div class="infodiv">
                <h3><?php echo __('Sync Orders', 'duellintegration') ?></h3>
                <p><?php echo __('Manual sync orders with Duell', 'duellintegration') ?></p>
                <a href="javascript:void(0)" data-type="sync_orders" class="syncbutton manual-cron"><?php echo __('Run now', 'duellintegration') ?></a>
              </div>

            </div>



            <div class="infodiv txtL">
              <h3>Setup Cronjobs</h3>
              <div><b>Product Sync every 3 hours:</b>  0 */3 * * * curl  <?php echo get_site_url(); ?>/wp-cron.php?doing_wp_cron >/dev/null 2>&1</div>
              <div><b>Price Sync every 30 minutes: </b> */30 * * * * curl  <?php echo get_site_url(); ?>/wp-cron.php?doing_wp_cron >/dev/null 2>&1</div>
              <div><b>Stocks Sync every 30 minutes: </b> */30 * * * * curl  <?php echo get_site_url(); ?>/wp-cron.php?doing_wp_cron >/dev/null 2>&1</div>
              <div><b>Orders Sync every night 3am: </b> 0 3 * * * curl  <?php echo get_site_url(); ?>/wp-cron.php?doing_wp_cron >/dev/null 2>&1</div>
            </div>


          </div>


        </div>
        <?php
    }

    public function setup_action_javascript() {
        ?><script>

                    (function ($) {
                      var $output = $('#manual-cron-output');


                      $('.manual-cron').click(function () {

                        console.log($(this).attr('data-type'))
                        jQuery.ajax({
                          type: "POST",
                          url: ajaxurl,
                          data: {action: 'manual_run_cron_action', param: $(this).attr('data-type')},
                          success: function (data) {
                            $output.html(data.response);
                          },
                          error: function (jqXHR, textStatus, errorThrown) {
                            $output.html('<code>ERROR</code> ' + textStatus + ' ' + errorThrown);
                          }
                        }).done(function (msg) {
                          // alert("Data Saved: " + msg.response);
                          $output.html('<code>OK</code>' + msg.response);
                        });

                      });
                    }(jQuery));
        </script>
        <?php
    }

    public function setup_sections() {
        add_settings_section('duell_configuration_section', 'Configure', array($this, 'section_callback'), 'duellintegration');
    }

    public function section_callback($arguments) {
        switch ($arguments['id']) {
            case 'duell_configuration_section':
                echo 'Note: Make sure you have API access in Duell manager section.';
                break;
        }
    }

    public function setup_fields() {
        $fields = array(
            array(
                'uid' => 'duellintegration_client_number',
                'label' => __('Client Number', 'duellintegration'),
                'section' => 'duell_configuration_section',
                'type' => 'number',
                'placeholder' => __('Client Number', 'duellintegration'),
                'class' => "",
                'default' => '',
                'helper' => '',
                'supplimental' => ''
            ),
            array(
                'uid' => 'duellintegration_client_token',
                'label' => __('Client Token', 'duellintegration'),
                'section' => 'duell_configuration_section',
                'type' => 'text',
                'placeholder' => __('Client Token', 'duellintegration'),
                'class' => "regular-text ltr",
                'default' => '',
                'helper' => '',
                'supplimental' => ''
            ),
            array(
                'uid' => 'duellintegration_stock_department_token',
                'label' => __('Stock Department Token', 'duellintegration'),
                'section' => 'duell_configuration_section',
                'type' => 'text',
                'placeholder' => __('Stock Department Token', 'duellintegration'),
                'supplimental' => 'Enter the department token from which stock will fetch',
                'class' => "regular-text ltr",
                'default' => '',
                'helper' => ''
            ),
            array(
                'uid' => 'duellintegration_order_department_token',
                'label' => __('Order Department Token', 'duellintegration'),
                'section' => 'duell_configuration_section',
                'type' => 'text',
                'placeholder' => __('Order Department Token', 'duellintegration'),
                'supplimental' => 'Enter the department token from in which order will save',
                'class' => "regular-text ltr",
                'default' => '',
                'helper' => ''
            ),
            array(
                'uid' => 'duellintegration_log_status',
                'label' => __('Enable Log', 'duellintegration'),
                'section' => 'duell_configuration_section',
                'type' => 'select',
                'options' => array(
                    '1' => 'Yes',
                    '0' => 'No'
                ),
                'default' => 0,
                'class' => "",
                'helper' => '',
                'supplimental' => ''
            ),
        );
        foreach ($fields as $field) {
            add_settings_field($field['uid'], $field['label'], array($this, 'field_callback'), 'duellintegration', $field['section'], $field);
            register_setting('duellintegration', $field['uid'], array($this, 'plugin_validate_' . $field['uid'] . '_option'));
        }
        register_setting('duellintegration', 'duellintegration_api_access_token');
    }

    public function field_callback($arguments) {

        $value = get_option($arguments['uid']);

        if (!$value) {
            $value = $arguments['default'];
        }

        switch ($arguments['type']) {
            case 'text':
            case 'password':
            case 'number':
                printf('<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" class="%5$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value, $arguments['class']);
                break;
            case 'textarea':
                printf('<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50" class="%4$s">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value, $arguments['class']);
                break;
            case 'select':
            case 'multiselect':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                    $attributes = '';
                    $options_markup = '';
                    foreach ($arguments['options'] as $key => $label) {
                        if ($arguments['type'] === 'multiselect') {
                            $options_markup .= sprintf('<option value="%s" %s>%s</option>', $key, selected($value[array_search($key, $value, true)], $key, false), $label);
                        } else {
                            $options_markup .= sprintf('<option value="%s" %s>%s</option>', $key, selected($value, $key, false), $label);
                        }
                    }
                    if ($arguments['type'] === 'multiselect') {
                        $attributes = ' multiple="multiple" ';
                        printf('<select name="%1$s[]" id="%1$s" %2$s class="%4$s">%3$s</select>', $arguments['uid'], $attributes, $options_markup, $arguments['class']);
                    } else {
                        printf('<select name="%1$s" id="%1$s" %2$s class="%4$s">%3$s</select>', $arguments['uid'], $attributes, $options_markup, $arguments['class']);
                    }
                }
                break;
            case 'radio':
            case 'checkbox':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                    $options_markup = '';
                    $iterator = 0;
                    foreach ($arguments['options'] as $key => $label) {
                        $iterator++;
                        $options_markup .= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked($value[array_search($key, $value, true)], $key, false), $label, $iterator);
                    }
                    printf('<fieldset>%s</fieldset>', $options_markup);
                }
                break;
        }

        if ($helper = $arguments['helper']) {
            printf('<span class="helper"> %s</span>', $helper);
        }

        if ($supplimental = $arguments['supplimental']) {
            printf('<p class="description">%s</p>', $supplimental);
        }
    }

    public function plugin_validate_duellintegration_log_status_option($input) {
        return sanitize_text_field($input);
    }

    function plugin_validate_duellintegration_client_number_option($input) {



        if (is_null($input) || $input == '' || !is_numeric($input) || strlen($input) != 6) {
            $input = get_option('duellintegration_client_number');
            add_settings_error('duellintegration_messages', 'duellintegration_messages', 'Incorrect value entered in client number!', 'error');
        }

        return sanitize_text_field($input);
    }

    function plugin_validate_duellintegration_client_token_option($input) {


        if (is_null($input) || $input == '') {
            $input = get_option('duellintegration_client_token');
            add_settings_error('duellintegration_messages', 'duellintegration_messages', 'Incorrect value entered in client token!', 'error');
        }

        return sanitize_text_field($input);
    }

    function plugin_validate_duellintegration_stock_department_token_option($input) {


        if (is_null($input) || $input == '') {
            $input = get_option('duellintegration_stock_department_token');
            add_settings_error('duellintegration_messages', 'duellintegration_messages', 'Incorrect value entered in stock department token!', 'error');
        }

        return sanitize_text_field($input);
    }

    function plugin_validate_duellintegration_order_department_token_option($input) {


        if (is_null($input) || $input == '') {
            $input = get_option('duellintegration_order_department_token');
            add_settings_error('duellintegration_messages', 'duellintegration_messages', 'Incorrect value entered in order department token!', 'error');
        }

        return sanitize_text_field($input);
    }

    public function enqueue_admin_scripts_and_styles() {

        $doing_wp_cron = sprintf('%.22F', microtime(true));
        $url = add_query_arg('doing_wp_cron', $doing_wp_cron, site_url('wp-cron.php'));
        $timeout = apply_filters('manual-cron-timeout', 20000);
        $script_vars = array(
            'url' => $url,
            'timeout' => $timeout,
        );

        wp_enqueue_style('duellintegration_admin', plugin_dir_url(__FILE__) . '/assets/css/duellintegration.css');

        //wp_enqueue_script('duellintegration_admin', plugin_dir_url(__FILE__) . '/assets/js/duellintegration.js');
        //wp_localize_script('duellintegration_admin', 'DUELLMANUALCRON', $script_vars);
    }

}

new Duellintegration();
