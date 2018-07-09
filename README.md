# Magento Magmi Via Cron Extension
Magento extension to run Magmi via Magento's cron
Note: This only works if you have Magmi (Magento Mass Importer)

# What does this do?
Magmi allows you to make changes to products in bulk and fast. Its fast because its a direct import into the database. So use Magmi with caution and backup your database before you import.

This extension allows you to run Magmi automatically using Magento's cron. Great for updating product pricing automatically every day, or changing stock levels in bulk once a week.

# Can I use it out the box? 
You need to make sure you have Magmi on your server.
Then:
- On line 10 & 11 of app/code/local/Oli/Magmi/Cron.php change the "/importer/...." to the location of your Magmi install. Eg, "/magmi/...."
- On line 38 of app/code/local/Oli/Magmi/Cron.php change this to match the name of the profile you have created in Magmi. You can use 'default' if you want to use the default profile however this is worth looking at because you want to make sure On the Fly indexing is turned off becauase this extension re-index's your magento store at the end. If you use a profile that has On the Fly indexing, it will re-index after each product, taking up a lot of server load for no reason. 

# Customisation
- On line 36 of app/code/local/Oli/Magmi/Cron.php this sets which Magento settings you want to re-index. The ones I add are:
 catalog_product_attribute
 catalog_product_price
 cataloginventory_stock
 You may want to add more or change these.
- On line 37 of app/code/local/Oli/Magmi/Cron.php this is Magmi mode. "create" creates and updates items, "update" updates only, "xcreate" creates only.

# More info
You can find more information about the Magmi DataDump API here -> http://wiki.magmi.org/index.php/Magmi_Datapump_API
