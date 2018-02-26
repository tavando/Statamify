# Statamify

Statamify is **FREE** Shopify-like ecommerce addon for Statamic. Copy the files to your main Statamic folder and you are set to go.

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
![Order view](/order-view.jpg?raw=true "Order view")

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

### 2.3. Orders
- **Customer Details** - beside form for editing customer details of order, added additional card on the sidebar to show summary of data

- **Countries & Regions** - use custom-written addon called *Statamify Countries* to handle easily countries and respective regions/states

- **Track Shipping** - add details for tracking

### 2.4. Coupons
- **Different types** - percentage, fixed, free shipping
- **Limits** - limit per users, countries, total usage, date range

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

 - Whole Ecommerce Store :)

<a name="disclaimer"></a>
## 5. Disclaimer
I'm not the pro expert of PHP like guys from Statamic, I hate writing docs and I work alone - that's why everyone who'd like to help is invited.

License
----

MIT
