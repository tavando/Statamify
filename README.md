# Statamify
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

Statamify is **FREE** Shopify-like ecommerce addon for Statamic. 

Check demo: http://demo.statamify.com/

*Included: homepage, products listing (/store is complex, relations like /store/types/dresses are simple), product view, cart, checkout (in progress)*

To install:
1) Copy the files to your main Statamic folder
2) Add new User Role called Customer
3) Go to Settings > Users and set New User Roles to Customer. If you want, set Login Type as email (we will use emails in all demo templates)

Currently it's not production ready and fully working eCommerce store. It's still in development.

**List of contents**
- [1. Screenshots](#screenshots)
- [2. Features](#features)
- [3. API](#api)
- [4. Todos](#todos)
- [5. Disclaimer](#disclaimer)

<a name="screenshots"></a>
## 1. Screenshots
![All products](/screenshot-products.jpg?raw=true "All products")
![General Settings for Product](/screenshot-product-new-general.jpg?raw=true "General Settings for Product")
![Relation Settings for Product](/screenshot-product-new-relation.jpg?raw=true "Relation Settings for Product")
![Order view](/screenshot-order.jpg?raw=true "Order view")
![Order view customer](/screenshot-order-view.jpg?raw=true "Order view - Customer Details")

<a name="features"></a>
## 2. Features
Here is the list of currently implemented features

### 2.1. Settings
- **Shipping Zones** - add price/weight based rates to different zones or rest of the world

- **Order ID Format** - format the id of the orders as you like

### 2.2. Products
- **Listing** - new listing view with the most important details about your product, like image, inventory, relations (with sorting)

- **Catalog** - three different relation types to categorize products
  - Type - single, like "Shirts", "Tablets"
  - Vendor - single, like "Nike", "Apple"
  - Collections - many, like "New Arrivals", "Our Choice" (**don't** confuse it with Statamic collections)

- **Variants** - use custom-written addon called *Statamify Variants* to handle complex products with individual prices, inventory, etc.

- **Two-way binding** - setting type, vendor or collections updates respective type of relation and vice versa

- **Filters** - build simple or complex filters on frontend using URL, example:

  `/store?sort=price:desc&filter=type:dresses|watches;collection:new-arrivals;price:>50;price:<100`

  Get `dresses` or `watches` from collection `new-arrivals` which price is `higher or equal 50` and `lower or equal 100` and sort it by `price descending`

### 2.3. Orders
- **Customer Details** - beside form for editing customer details of order, added additional card on the sidebar to show summary of data

- **Countries & Regions** - use custom-written addon called *Statamify Countries* to handle easily countries and respective regions/states

- **Track Shipping** - add details for tracking

- **Emails on Queue** - emails are sent via Cron to speed up checkout and status change

### 2.4. Coupons
- **Different types** - percentage, fixed, free shipping
- **Limits** - limit per users, countries, total usage, date range

### 2.5. Cart
- **Instances** - there can be many instances of different carts. It can be helpful for example to create Wishlist (so one cart is CART, and second instance is WISHLIST)

<a name="api"></a>
## 3. API
Below are API ends to use with Statamify. Remember to use CSRF _token for POST ends

### 3.1. Get cart
Get cart in JSON. Adding and updating also returns cart;

```
  GET /!/statamify/cart
```

### 3.2. Add to cart
Add new item to cart (if item exists in cart, update function will fire)

```
  POST /!/statamify/cart_add
  product: ##PRODUCT_ID##
  variant: ##VARIANT_ID (if simple, leave "")
  quantity: 2
```

### 3.3. Update cart
Update item in cart

```
  POST /!/statamify/cart_update
  item_id: ##CART ITEM ID## (cart generates unique id for all cart items)
  quantity: 1 (if zero, item will be removed from cart)
```

<a name="todos"></a>
## 4. Todos
 - Coupons in checkout
 - Refund feature
 - Analytics
 - Multi Currency
 - Multi Language
 - Digital Goods for Download

<a name="disclaimer"></a>
## 5. Disclaimer
I'm not the pro expert of PHP like guys from Statamic, I hate writing docs and I work alone - that's why everyone who'd like to help is invited.
