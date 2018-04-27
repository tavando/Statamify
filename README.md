# Statamify
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

Statamify is **FREE** Shopify-like ecommerce addon for Statamic. 

Check demo: http://demo.statamify.com/

Currently it's not production ready and fully working eCommerce store. It's still in development.

To install:
- Copy the `site` folder and put in respective place
- Open command and type: `php please statamify:install`. This will add all necessary routes, fieldsets and collections
- Go to `site/statamic/` folder and install OmniPay plugins (currently only Stripe is integrated: `composer require omnipay/stripe:~2.0`)

## Screenshots
![All products](/screenshot-products.jpg?raw=true "All products")
![General Settings for Product](/screenshot-product-new-general.jpg?raw=true "General Settings for Product")
![Relation Settings for Product](/screenshot-product-new-relation.jpg?raw=true "Relation Settings for Product")
