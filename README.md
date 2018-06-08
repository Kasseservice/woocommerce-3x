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

Download the plugin files. Download from [here](https://github.com/Kasseservice/woocommerce-3x/)

### Step 2: Upload the Plugin

* Upload plugin thru ftp to the Wordpress root directory > wp-content > plugins 

### Step 3: Install and Active Plugin

On Wordpress Admin

* Goto > Wp-admin > Plugins
* Find "Duell Integration" 
* Click on "Active" link to activate the plugin.

### Step 4: Setup duell credential

**Note:** Need API related access available in Duell application. Find the below details in Duell Manager area > API-oppsett 

* **Client Number:** Required for API authentication
* **Client Token:** Required for API authentication
* **Stock Department:** Copy the department token in which stock need to manage.
* **Order Department:** Copy the department token in which Woocommerce order save.
* **Update existing products:** If flag is enabled, only update the existing product information
* **Enable Log:** In case of enable, it will save all logs in wp-content folder with file name  **`duell-YYYY-mm-dd.log`**
* **Enable Sync:** If flag is enabled, only synced data with Duell

### Step 5: Setup cron job manually 

If Wordpress auto cron not work, than user have to setup cron job command manually to the server. [Here](https://developer.wordpress.org/plugins/cron/hooking-into-the-system-task-scheduler/)

Cron job can be set either with cURL or wget.

* Every 15 minutes

  ```bash
  */15 * * * * curl https://<YOURWEBSHOP.COM>/wp-cron.php?doing_wp_cron >/dev/null 2>&1
  ```
  
* Every 3 hours

  ```bash
  * */3 * * * curl https://<YOURWEBSHOP.COM>/wp-cron.php?doing_wp_cron >/dev/null 2>&1
  ```
 
## How to Use
-------
On WordPress administration panel 

* Goto **`Plugins > Installed Plugins`** and scroll down until to find **`Duell Integration`**.
* Activate plugin first. 
* Click on **`Settings`** to configure it as mentioned in **[Step 4](#step-4-setup-duell-credential)**.

### Products Sync

#### Product Sync Workflow

* Product sync needs to be done as a first step. The plugin checks if **`product number`** of Duell exists in the Woocommerce product at **`SKU`** field. 
* If product number already exists, plugin only update product information if **`Update existing product`** flag is enabled. 
* If product is not exists, plugin adds new products 
    * Product to **`Pending`** status 
    * Stock to **`OutOfStock`** 
    * Stock value to **`0`** 
* Products need to manually **`published`** to enable it for sale.

Check below **[Prices](#prices)** section point for product price.

### Stocks

Second step is to synchronize latest stocks from Duell. If stock greater than **`0`** then plugin updates stock status to **`InStock`** and stock value to **`Stock`** to latest stock number.

### Prices

Synchronize products with latest prices. Plugin manages price based on Woocommerce setting `Prices entered with tax`.
  
  * **`Yes, I will enter prices inclusive of tax`:** Adds price inclusive tax.
  * **`No, I will enter prices exclusive of tax`:** Adds price exclusive tax. Woocommerce add tax based on flag **`Enable tax rates and calculations`** and configured tax. If tax flag enabled then Woocommerce apply tax based on setting in **`Tax`** tab.

**NOTE:** Once product sync and tthen if there is change in change Woocommerce settings, the product price will not get updated.  In such case, the price needs to manually change for individual products.

### Subtract stocks from Duell

Whenever a new order placed from Webshop or Admin side, plugin subtracts stocks from Duell. 


* Webshop order hook. The Hook can be changed as per user requirement.

  **`woocommerce_thankyou`**
  
* Admin order hook. This hook cannot be changed.

  **`woocommerce_process_shop_order_meta`**

### Orders

* Orders are synchronize once in a day (can be set by user; preferred 3 AM). 
* Plugin adds an extra column (**Duell Order No.**) in Woocommerce admin order list page. User can search Duell order number from search box.
* Plugin adds **`SHIPPING`** price as a product in Duell on order sync.


Recommended Tools
-------

### Cron Tools

There are many cron job related plugins available to manage cron jobs.

* [Advanced Cron Manager](https://wordpress.org/plugins/advanced-cron-manager/)
* [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/)
* [WP-Cron Control](https://wordpress.org/plugins/wp-cron-control/)

Any of the above tools should provide proper way  to manage cron scheduling.

LICENSE
-------

The module is open source, and made available via an 'GNU GENERAL PUBLIC LICENSE', which basically means you can do whatever you like with it as long as you retain the copyright notice and license description - see [LICENSE](../master/LICENSE) for more information.


