Duell woocommerce-3x module
=====================

The purpose of this plugin is to manage & sync products, stocks and orders at both Woocommerce 3.x webshop and Duell. 

Prerequisites
-------------

This module requires Wordpress 4.x,  Woocommerce 3.x.

Dependencies
-------------

This module requires Woocommerce 3.x plugin installed and active.

Frequently Asked Questions
-------------

| Action      |    Support         | Comment  |
| :------------- |:-------------:| :-----|
| Insert product category from DUELL to WP | Yes | If `Create new product category` flag is enabled. |
| Insert product category from WP to DUELL | No | |
| Update product category from DUELL to WP | No | |
| Update product category from WP to DUELL | No | |
|  Insert product inc price from DUELL to WP     | Yes | Every 3rd hour. |
|  Insert product inc price from WP to DUELL     | Yes | In case we need to create an order in DUELL and product does not exist. New product must have SKU. |
|  Update product from DUELL to WP     | Yes | Every 15 minutes. Currently supported only title and description update.  |
|  Update product from WP to DUELL     | No |  |
|  Update price from DUELL to WP     | Yes | Every 15 minutes. If `update existing product` flag is enabled. |
|  Update price from WP to DUELL     | No |  |
|  Update stock from DUELL to WP     | Yes | Every 15 minutes. If `update existing product` flag is enabled and product `Manage stock`  checkbox is checked. |
|  Update stock from WP to DUELL | Yes | When you receive new order in WP. If order stocks update, it will not updating in DUELL. |
| Insert customer from WP to DUELL | Yes | In case we need to create an order in DUELL and customer does not exist. |
|  Insert customer from DUELL to WP | No | |
|  Insert orders from WP to DUELL  | Yes | Every 15 minutes. |



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

#### Duell Configuration

* **Client Number:** Required for API authentication
* **Client Token:** Required for API authentication
* **Enable Log:** In case of enable, it will save all logs datewise inside wp-content > uploads > duell folder with file name  **`YYYY-mm-dd.log`**
* **Enable Sync:** If flag is enabled, auto sync will work. If disabled then have to manually sync.

#### Product Configuration

* **Create new product category:** If flag is enabled, only create new product category in Woocommerce. 
* **Create new products:** If flag is enabled, only create new products in Woocommerce.
* **Update existing products:** If flag is enabled, only update the existing product information in Woocommerce.

#### Price Configuration

* **Update existing products:** If flag is enabled, only update the existing product price with Duell price.

#### Stock Configuration

* **Stock Department:** Copy the department token in which stock need to manage.
* **Update existing products:** If flag is enabled, only update the existing product stock with Duell stock.

#### Order Configuration

* **Order Department:** Copy the department token in which Woocommerce order save.
* **Start from Order No.:** Enter the order number from which start sending to Duell.
* **Order Status:** Select the which status orders sends to Duell.
* **Create new products:** If flag is enabled, only create new products to Duell, otherwise exclude the order.

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
 
How to Use
-------
On WordPress administration panel 

* Goto **`Plugins > Installed Plugins`** and scroll down until to find **`Duell Integration`**.
* Activate plugin first. 
* Click on **`Settings`** to configure it as mentioned in **[Step 4](#step-4-setup-duell-credential)**.

### Products Sync Workflow

* Product sync needs to be done as a first step. The plugin checks if **`product number`** of Duell exists in the Woocommerce product at **`SKU`** field. 
* If product number already exists, plugin only update product information if **`Update existing product`** flag is enabled. 
* If product is not exists, plugin adds new products 
    * Product to **`Pending`** status 
    * Stock to **`OutOfStock`** 
    * Stock value to **`0`** 
* Products need to manually **`published`** to enable it for sale.
* Products with flag `View in online webshop` only sync.


### Prices Sync Workflow

Synchronize products with latest prices only if `Update existing products` flag is enabled. Plugin manages price based on Woocommerce setting `Prices entered with tax`.
  
  * **`Yes, I will enter prices inclusive of tax`:** Adds price inclusive tax.
  * **`No, I will enter prices exclusive of tax`:** Adds price exclusive tax. Woocommerce add tax based on flag **`Enable tax rates and calculations`** and configured tax. If tax flag enabled then Woocommerce apply tax based on setting in **`Tax`** tab.

**NOTE:** Once product sync and then if there is change in change Woocommerce settings, the product price will not get updated.  In such case, the price needs to manually change for individual products.

### Stocks Sync Workflow

Second step is to synchronize latest stocks from Duell only if `Stock department` is set and `Update existing products` flag is enabled. If stock greater than **`0`** and product `Manage stock?` checkbox is checked then plugin updates stock status to **`InStock`** and stock value to **`Stock`** to latest stock number.

### Subtract stocks from Duell Workflow

Whenever a new order placed from Webshop or Admin side, plugin subtracts stocks from Duell if `Stock department` is set. 


* Webshop order hook. The Hook can be changed as per user requirement.

  **`woocommerce_thankyou`**
  
* Admin order hook. This hook cannot be changed.

  **`woocommerce_process_shop_order_meta`**

### Orders Sync Workflow

* Orders are synchronize only if `Order department` is set.
* Orders are start sync from the entered order number in `Start from orde No.` field. 
* Only selected status orders are synced `Order status`. If `Dont Sync` is selected then it will not sync.
* Plugin creates new products to Duell if `Create new product` is enabled, otherwise exclude the orders.
* Plugin adds **`SHIPPING`** price as a product in Duell on order sync.
* Plugin adds all new products under category `Diverse` in Duell.
* Plugin adds an extra column (**Duell Order No.**) in Woocommerce admin order list page. User can search Duell order number from search box.


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


