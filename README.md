<img src="./view/adminhtml/web/images/uber_logo_menu.svg" align="right" width="48"> <p>Official Module - Magento 2</p>
<hr>

## Description
Official module [Uber](https://uber.com/) for Magento 2. The module was developed using Uber API documentation [API Docs](https://developer.uber.com/docs/deliveries/overview).

### Installation
The module requires Magento 2.0.x or higher for its correct operation. It will need to be installed using the Magento console commands.

```sh
composer require improntus/module-uber-magento-2
```

Developer installation mode

```sh
php bin/magento deploy:mode:set developer
php bin/magento module:enable Improntus_Uber
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy es_AR en_US
php bin/magento setup:di:compile
```

Production installation mode

```sh
php bin/magento module:enable Improntus_Uber
php bin/magento setup:upgrade
php bin/magento deploy:mode:set production
```

## Author

[![N|Solid](https://improntus.com/wp-content/uploads/2022/05/Logo-Site.png)](https://www.improntus.com)