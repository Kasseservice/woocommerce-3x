<?php
defined('ABSPATH') or die('No script kiddies please!');
/*
  Plugin Name: Duell Woocommerce Integration
  Plugin URI: https://kasseservice.no/
  Description: Plugin used to sync orders, products, customer with Duell POS
  Author: kasseservice
  Version: 1.0
  Author URI: https://kasseservice.no/
 */

function duellintegration_install() {
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

//    add_site_option('duellintegration_client_number', '');
//    add_site_option('duellintegration_client_token', '');
//    add_site_option('duellintegration_department_token', '');
}

register_activation_hook(__FILE__, 'duellintegration_install');


add_action('admin_menu', 'duellintegration_plugin_menu');

function duellintegration_plugin_menu() {
    /* http://clivern.com/adding-menus-and-submenus-for-wordpress-plugins/
     * Page title – used in the title tag of the page (shown in the browser bar) when it is displayed.
      Menu title – used in the menu on the left.
      Capability – the user level allowed to access the page.
      Menu slug – the slug used for the page in the URL.
      Function – the name of the function you will be using to output the content of the page.
      Icon – A url to an image or a Dashicons string.
      Position – The position of your item within the whole menu.
     */
    add_menu_page('Duell Integration Settings', 'Duell Integration', 'administrator', 'duell-settings', 'duellintegration_plugin_settings_page', plugins_url() . '/duellintegration/assets/images/duell-icon.png');
    /*
      parent_slug: Slug of the parent menu item.
      page_title: The page title.
      menu_title: The submenu title displayed on dashboard.
      capability: Minimum capability to view the submenu.
      menu_slug: Unique name used as a slug for submenu item.
      function: A callback function used to display page content.
     */
    add_submenu_page('duell-settings', 'Settings', 'Settings', 'administrator', 'duell-settings', 'duellintegration_plugin_settings_page');
    add_submenu_page('duell-settings', 'Logs', 'Logs', 'administrator', 'duell-integration-logs', 'duellintegration_plugin_settings_page');
}

function duellintegration_plugin_settings_page() {

    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // add error/update messages
    // check if the user have submitted the settings
    // wordpress will add the "settings-updated" $_GET parameter to the url
    if (isset($_GET['settings-updated'])) {
        // add settings saved message with the class of "updated"
        add_settings_error('duellintegration_messages', 'duellintegration_message', __('Settings Saved', 'duellintegration'), 'updated');
    }

    // show error/update messages
    settings_errors('duellintegration_messages');
    ?>
    <div class="wrap">
      <h1><?php echo __('Duell Integration', 'duellintegration') ?></h1>

      <form method="post" action="options.php">
        <?php settings_fields('duellintegration'); ?>
        <?php do_settings_sections('duellintegration'); ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php echo __('Client Number', 'duellintegration') ?></th>
            <td><input type="text" name="duellintegration_client_number" value="<?php echo esc_attr(get_option('duellintegration_client_number')); ?>" class="regular-text ltr" /></td>
          </tr>

          <tr valign="top">
            <th scope="row"><?php echo __('Client Token', 'duellintegration') ?></th>
            <td><input type="text" name="duellintegration_client_token" value="<?php echo esc_attr(get_option('duellintegration_client_token')); ?>" class="regular-text ltr" /></td>
          </tr>

          <tr valign="top">
            <th scope="row"><?php echo __('Department Token', 'duellintegration') ?></th>
            <td><input type="text" name="duellintegration_department_token" value="<?php echo esc_attr(get_option('duellintegration_department_token')); ?>" class="regular-text ltr" /></td>
          </tr>
        </table>

        <?php submit_button(); ?>

      </form>
    </div>
    <?php
}

//==load default options
add_action('admin_init', 'duellintegration_plugin_settings');

function duellintegration_plugin_settings() {
    register_setting('duellintegration', 'duellintegration_client_number');
    register_setting('duellintegration', 'duellintegration_client_token');
    register_setting('duellintegration', 'duellintegration_department_token');
}

/**
 * Add stylesheet to the page
 */
add_action('admin_enqueue_scripts', 'duellintegration_stylesheet_to_admin');

function duellintegration_stylesheet_to_admin() {
    wp_enqueue_style('duellintegration_css', plugins_url('/assets/css/duellintegration.css', __FILE__));
}
