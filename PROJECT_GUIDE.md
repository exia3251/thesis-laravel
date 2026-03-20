# Project File Guide

This guide explains the most important files so you can edit the project manually during final preparation.

## 1. Routes

### `routes/web.php`
Main session-based routes for the app.

Important sections:
- admin login/logout
- admin pages
- admin AJAX endpoints under `/admin-api`
- customer login/register/logout
- customer pages
- customer AJAX endpoints under `/shop-api`

If you need to add a new page or new backend action, this is usually the first file to update.

### `routes/api.php`
Contains API endpoints used by some frontend product/customer reads.
Used especially for product catalog fetches.

## 2. Customer Controllers

### `app/Http/Controllers/Customer/ShopController.php`
Handles:
- storefront page
- product list API
- product details API/view

If product cards or product detail data need changes, start here.

### `app/Http/Controllers/Customer/CartController.php`
Handles:
- add to cart
- update cart quantity
- remove cart item
- get cart data

If cart behavior breaks, this is one of the first places to inspect.

### `app/Http/Controllers/Customer/OrderController.php`
Handles:
- checkout / placing order
- order history
- receipt/order detail page
- payment request submission

This is the most important customer transaction file.

### `app/Http/Controllers/Customer/ProfileController.php`
Handles:
- customer profile data
- password change

## 3. Admin Controllers

### `app/Http/Controllers/Admin/DashboardController.php`
Handles dashboard stats and analytics.

### `app/Http/Controllers/Admin/ProductController.php`
Handles:
- product CRUD
- image uploads
- bulk import

If you want to edit product validation, import behavior, or image behavior, use this file.

### `app/Http/Controllers/Admin/InventoryController.php`
Handles:
- stock in
- stock out
- inventory listing
- inventory transaction history

Also now logs staff inventory actions.

### `app/Http/Controllers/Admin/SalesController.php`
Handles:
- sales list
- manual admin sale creation
- payment status update
- delivery status update
- approve/reject payment requests

This is the key admin sales/payment workflow file.

### `app/Http/Controllers/Admin/UserManagementController.php`
Handles:
- super admin user management
- logs page data

### `app/Http/Controllers/Admin/ReportController.php`
Handles:
- reports page
- sales report data
- inventory report data
- CSV export

## 4. Models

### `app/Models/Product.php`
Product data and product relationships.

### `app/Models/Inventory.php`
Inventory quantities and stock helpers.

### `app/Models/Sale.php`
Customer/admin sales orders.

### `app/Models/SaleItem.php`
Products inside each sale.

### `app/Models/PaymentRequest.php`
Customer payment requests waiting for admin review.

### `app/Models/ActivityLog.php`
Audit log model for super admin monitoring.

## 5. Customer Views

### `resources/views/customer/shop.blade.php`
Main storefront homepage and catalog.

### `resources/views/customer/product-details.blade.php`
Single product page.

### `resources/views/customer/cart.blade.php`
Cart and checkout UI.

### `resources/views/customer/orders.blade.php`
Customer order list.

### `resources/views/customer/order-details.blade.php`
Receipt page and payment request section.

### `resources/views/customer/profile.blade.php`
Customer profile UI.

### `resources/views/customer/login.blade.php`
Customer login page.

### `resources/views/customer/register.blade.php`
Customer registration page.

## 6. Admin Views

### `resources/views/admin/dashboard.blade.php`
Admin dashboard UI.

### `resources/views/admin/products.blade.php`
Product management UI.

### `resources/views/admin/inventory.blade.php`
Inventory management UI.

### `resources/views/admin/sales.blade.php`
Sales management UI and payment request approval UI.

### `resources/views/admin/users.blade.php`
Super admin user management and activity log UI.

### `resources/views/admin/reports.blade.php`
Reports and exports UI.

## 7. Database Files

### `database/migrations`
Contains the database structure history.

Important recent areas:
- sales fields
- product image path
- current session tracking
- delivery info on sales
- payment request table

### `database/seeders/ProductCatalogSeeder.php`
Contains seeded real products.

### `database/seeders/DatabaseSeeder.php`
Main seeder entry point.

## 8. Static Assets

### `public/images/gcash-qr-placeholder.svg`
Current placeholder QR for GCash flow.
Replace this with the real QR image before demo if available.

## 9. How To Change Common Things

### Change customer payment flow
- `app/Http/Controllers/Customer/OrderController.php`
- `resources/views/customer/cart.blade.php`
- `resources/views/customer/order-details.blade.php`

### Change admin approval flow
- `app/Http/Controllers/Admin/SalesController.php`
- `resources/views/admin/sales.blade.php`

### Change logs
- `app/Models/ActivityLog.php`
- controller where the action happens
- `resources/views/admin/users.blade.php`

### Change product cards / storefront look
- `resources/views/customer/shop.blade.php`

### Change validation
- usually in the controller method handling the form

## 10. Safe Final Advice

For the last 2 days:
- avoid changing database structure again unless necessary
- avoid adding brand new modules
- prefer small controller/view fixes
- test every changed flow manually after edits
