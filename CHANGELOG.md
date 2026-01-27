CHANGELOG
---------

### 1.1.6 - 2026-01-27
- Adjust `manifest_total_value` to be in cents.

### 1.1.5 - 2025-08-11
- We've added a new setting to determine whether we'll send additional address information when creating the shipment.
 
### 1.1.4 - 2025-08-01
- Modify the format of the "dropoff_address" field in the data sent when creating the shipment.

### 1.1.3 - 2025-07-31
- Merge alternative branch 1.0.13 into Main.

### 1.1.2 - 2025-07-30
* Updated Monologuer in AbstractHandler.php

### 1.1.1 - 2025-07-24
* Fix the way the JSON object is formatted to create a new submission. 
* Specifically, we changed the "picture" object attribute to Boolean.

### 1.1.0 - 2025-07-16
* Compatibility with Adobe Commerce and Magento Open Source 2.4.8-p1

### 1.0.13 - 2025-07-28
- Add a new configuration to indicate which street lines are used in the delivery address.

### 1.0.12 - 2025-07-25
- Fix the way the JSON object is formatted to create a new submission.
- Specifically, we changed the "picture" object attribute to Boolean.
- This is an alternative branch that is born from version 1.0.10.

### 1.0.10 - 2025-05-28
- Fix: Adjust Uber Direct logo in Shipping Method (Storefront)

### 1.0.9 - 2025-03-17
- Added: A functionality is added that allows writing an Order History with the Warehouse assigned to the order.

### 1.0.8 - 2025-02-13
- Update: WarehouseRepositoryInterface / WarehouseRepository

### 1.0.7 - 2025-01-20
- Added: Events uber_shipment_create and uber_shipment_cancel were added
- Added: Transactional emails are added with shipment status updates. This option requires having Webhooks configured.
- Added: A cron job is added to synchronize the shipment status
- Added: Possibility to display a different title when quoting an order outside business hours.

### 1.0.6 - 2024-10-28
- Update: WarehouseRepositoryInterface / WarehouseRepository

### 1.0.5 - 2024-08-21
- Removed shipping method when the warehouse is not available

### 1.0.4 - 2024-08-16
- Fixed allowed shipping method

### 1.0.3 - 2024-08-16
- Fixed issue with store id in non-frontend area

### 1.0.2 - 2024-07-23
- Fixed issue when showing estimate price

### 1.0.1 - 2024-04-17
- Minor Fixes

### 1.0.0 - 2024-04-10
- Init module
