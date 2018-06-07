Duell woocommerce-3x module
=====================

The purpose of this plugin is to manage & sync products, stocks and orders at both wocommerce 3.x webshop and Duell. 

Prerequisites
-------------

This module requires Wordpress 4.x,  Wocommerce 3.x.


Installation
------------

### Step 1: Download the Plugin

Download the plugin files.

### Step 2: Upload the Plugin

* Please take backup of database. 
* Upload plugin to the wordpress root directory > wp-content > plugins 

### Step 3: Install and Active Plugin

* Goto > Wp-admin > Plugins
* Find "Duell Integration" 
* Click on "Active" link to activate the plugin.

### Step 4: Setup duell credential

**Note:** Make sure you have API related access in Duell application. Find the below details in duell manager area > API-oppsett 

* **Client Number:** Required for API authentication
* **Client Token:** Required for API authentication
* **Stock Department:** Copy the department token in which stock need to manage.
* **Order Department:** Copy the department token in which wocommerce order save.
* **Update existing products:** If this flag is enabled, only update the existing product information
* **Enable Log:** In case of enable, it will save all logs in wp-content with file name  "duell-YYYY-mm-dd.log"
* **Enable Sync:** If this flag is enabled, only synced products, stocks, prices and orders

### Step 5: Setup cron job with CURL

* Every 30 minutes

  ```bash
  */30 * * * * /usr/bin/curl http://<YOURWEBSHOP.COM>/wp-cron.php?doing_wp_cron >/dev/null 2>&1
  ```
  
* Every 3 hours

  ```bash
  * */3 * * * /usr/bin/curl http://<YOURWEBSHOP.COM>/wp-cron.php?doing_wp_cron >/dev/null 2>&1
  ```
* Every night 3am

  ```bash
  * 3 * * * /usr/bin/curl http://<YOURWEBSHOP.COM>/wp-cron.php?doing_wp_cron >/dev/null 2>&1
  ```
 
LICENSE
-------

The module is open source, and made available via an 'GNU GENERAL PUBLIC LICENSE', which basically means you can do whatever you like with it as long as you retain the copyright notice and license description - see [LICENSE] for more information.


