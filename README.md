Duell woocommerce-3x module
=====================

The purpose of this plugin is to manage & sync products, stocks and orders at both Woocommerce 3.x webshop and Duell. 

Prerequisites
-------------

This module requires Wordpress 4.x,  Woocommerce 3.x.

Dependencies
-------------

This module requires Woocommerce 3.x plugin installed and active.

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
* **Order Department:** Copy the department token in which Woocommerce order save.
* **Update existing products:** If flag is enabled, only update the existing product information
* **Enable Log:** In case of enable, it will save all logs in wp-content folder with file name  "duell-YYYY-mm-dd.log"
* **Enable Sync:** If flag is enabled, only synced data with Duell

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
 
## How to Use
-------
From your WordPress administration panel go to `Plugins > Installed Plugins` and scroll down until you find `Duell Integration`. You'll need to activate it first. Then click on `Settings` to configure it as mentioned in `Step 4`.

### Products

Very first step is to synchronize products from Duell. Plugin check `product number` of Duell exists in the Woocommerce product at `SKU` field. If product number already exists, plugin only update if `Update existing product` flag is enabled. If product is not exists, plugin add products in `Pending` status with `OutOfStock` status & `0` stock. Products need to manually `published` to use for sell.

Check `Prices` point regarding product price.

### Stocks

Second step is to synchronize latest stocks from Duell. If stock greater than `0` then plugin update stock status to `InStock` and `Stock` to latest stock number.

### Prices

Synchronize products with latest prices. Plugin manage price based on Woocommerce setting `Prices entered with tax`.
  
  * `Yes, I will enter prices inclusive of tax`: Plugin add price inclusive tax.
  * `No, I will enter prices exclusive of tax`: Plugin add price exclusive tax. Woocommerce add tax based on flag `Enable tax rates and calculations` and configured tax. If tax flag enabled then Woocommerce apply tax based on setting in `Tax` tab.

**NOTE:** Once product sync, it will not update product price if you change Woocommerce settings.

### Subtract stocks from Duell

Plugin subtract stocks from Duell when new order placed from Webshop or Admin side.   


* Webshop order implemented hook. You can change the hook as per your requirement.

  `woocommerce_thankyou`
  
* Admin order implemented hooks

  `woocommerce_process_shop_order_meta`

### Orders

* Orders are synchronize once in a day(preferred 3 AM). You can change time as per your requirement. 
* Plugin add extra column (Duell #) in Woocommerce admin order list page which shows Duell order number. You can search Duell order number from search box.
* Plugin add `SHIPPING` price as a product in Duell at time synchronize.


Recommended Tools
-------

### Cron Tools

There are many cron job related plugins avaialble to manage cron jobs.

* [Advanced Cron Manager](https://wordpress.org/plugins/advanced-cron-manager/)
* [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/)
* [WP-Cron Control](https://wordpress.org/plugins/wp-cron-control/)

Any of the above tools should provide you proper cron scheduling.

LICENSE
-------

The module is open source, and made available via an 'GNU GENERAL PUBLIC LICENSE', which basically means you can do whatever you like with it as long as you retain the copyright notice and license description - see [LICENSE](../master/LICENSE) for more information.


