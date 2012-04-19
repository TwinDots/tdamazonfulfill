#Amazon Fulfilment LemonStand Module

##Shipping Module

###Configuration Options

* Access Key ID
* Secret Access Key
* Available Shipping Speeds
	* Standard
	* Expedited
	* Priority
* Include fulfilment fee in shipping quote?
* Order status once fulfilled
* Order status if fulfilled failed

###Future configuration options that will be hardcoded for now

* FulfillmentPolicy, will default to FillOrKill so that all items have to be available.
* FulfillmentMethod, will default to consumer.

###How it works/notes

Once a successful payment has been made a request to create a fulfilment order is sent to Amazon if it is successful we change the order status accordingly.

Product Items are extended so that they have a flag called 'fulfilled by Amazon' & 'Amazon SKU number'

The inventory is synched every time an order is placed.

The inventory is also synched every time the admin logs in.

A PHP file is also present which can be used to sync via cron.

###Limitations

* Order status isn't reported back to LemonStand
