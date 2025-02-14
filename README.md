<img src="./view/adminhtml/web/images/uber_logo_menu.svg" align="right" width="48"> <p>Official Module - Adobe Commerce / Magento 2 Open Source</p>
<hr>

## Description
Official module [Uber](https://uber.com/) for Adobe Commerce / Magento 2 Open Source. The module was developed using Uber API documentation [API Docs](https://developer.uber.com/docs/deliveries/overview).

### Installation
The module requires Adobe Commerce / Magento 2 Open Source 2.3.x or higher for its correct operation. It will need to be installed using the Magento console commands.

```sh
composer require improntus/module-uber
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
