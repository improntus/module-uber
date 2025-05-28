CHANGELOG
---------

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
