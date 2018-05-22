<?php
defined('ABSPATH') or die('No script kiddies please!');
/*
  Plugin Name: Duell Integration
  Plugin URI: https://kasseservice.no/
  Description: Plugin used to sync orders, products, customer with Duell POS
  Author: kasseservice
  Version: 1.0
  Author URI: https://kasseservice.no/
 */

class Duellintegration {

    public function __construct() {

        register_activation_hook(__FILE__, array($this, 'setup_install'));
        register_deactivation_hook(__FILE__, array($this, 'setup_uninstall'));

        // Hook into the admin menu
        add_action('admin_menu', array($this, 'create_plugin_settings_page'));

        // Add Settings and Fields
        add_action('admin_init', array($this, 'setup_sections'));
        add_action('admin_init', array($this, 'setup_fields'));

        //admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts_and_styles'));
        add_action('admin_notices', array($this, 'update_notice'));
        add_action('admin_notices', array($this, 'error_notice'));
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
    }

    /*
     * Actions perform on de-activation of plugin
     */

    function setup_uninstall() {
        global $wpdb;
        $table = $wpdb->prefix . "duell_sync_logs";


        // drop a table
        $wpdb->query("DROP TABLE IF EXISTS $table");

        // for site options in Multisite
        delete_option('duellintegration_client_number');
        delete_option('duellintegration_client_token');
        delete_option('duellintegration_stock_department_token');
        delete_option('duellintegration_order_department_token');
        delete_option('duellintegration_api_access_token');
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

            <div class="col-right-hf">


              <div class="infodiv">
                <h3><?php echo __('Sync Products', 'duellintegration') ?></h3>
                <p><?php echo __('Manual sync products with Duell', 'duellintegration') ?></p>
                <a href="javascript:void(0)" class="syncbutton"><?php echo __('Do Now', 'duellintegration') ?></a>
              </div>

              <div class="infodiv">
                <h3><?php echo __('Sync Stocks', 'duellintegration') ?></h3>
                <p><?php echo __('Manual sync stocks with Duell', 'duellintegration') ?></p>
                <a href="javascript:void(0)" class="syncbutton"><?php echo __('Do Now', 'duellintegration') ?></a>
              </div>

            </div>

            <div class="col-right-hf borderL">

              <div class="infodiv">
                <h3><?php echo __('Sync Price', 'duellintegration') ?></h3>
                <p><?php echo __('Manual sync price with Duell', 'duellintegration') ?></p>
                <a href="javascript:void(0)" class="syncbutton"><?php echo __('Do Now', 'duellintegration') ?></a>
              </div>

              <div class="infodiv">
                <h3><?php echo __('Sync Orders', 'duellintegration') ?></h3>
                <p><?php echo __('Manual sync orders with Duell', 'duellintegration') ?></p>
                <a href="javascript:void(0)" class="syncbutton"><?php echo __('Do Now', 'duellintegration') ?></a>
              </div>

            </div>



            <div class="infodiv txtL">
              <h3>Setup Cronjobs</h3>
              <div><b>Product Sync every 3 hours:</b>  0 */3 * * * /usr/bin/curl  http://[yourwebshop.com]/duell/cron/product >/dev/null 2>&1</div>
              <div><b>Price Sync every 30 minutes: </b> */3 * * * * /usr/bin/curl  http://[yourwebshop.com]/duell/cron/prices >/dev/null 2>&1</div>
              <div><b>Stocks Sync every 30 minutes: </b> */3 * * * * /usr/bin/curl  http://[yourwebshop.com]/duell/cron/stocks >/dev/null 2>&1</div>
              <div><b>Orders Sync every night 3am: </b> 0 3 * * * /usr/bin/curl  http://[yourwebshop.com]/duell/cron/orders >/dev/null 2>&1</div>
            </div>


          </div>


        </div>
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
            )
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
                        $options_markup .= sprintf('<option value="%s" %s>%s</option>', $key, selected($value[array_search($key, $value, true)], $key, false), $label);
                    }
                    if ($arguments['type'] === 'multiselect') {
                        $attributes = ' multiple="multiple" ';
                    }
                    printf('<select name="%1$s[]" id="%1$s" %2$s class="%4$s">%3$s</select>', $arguments['uid'], $attributes, $options_markup, $arguments['class']);
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
        wp_enqueue_style('duellintegration_admin', plugin_dir_url(__FILE__) . '/assets/css/duellintegration.css');
    }

}

if (is_admin()) {
    new Duellintegration();
}
