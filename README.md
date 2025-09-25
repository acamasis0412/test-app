# E-commerce Order Management System (Backend)

This is a backend application for an E-commerce Order Management System built with Laravel. It includes features like authentication, product and category management, cart and order processing, mocked payments, role-based access control, middleware, reusable components, notifications, and caching.

## Table of Contents

- [Features](#features)
- [Setup Instructions](#setup-instructions)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Database Setup](#database-setup)
  - [Running Migrations and Seeders](#running-migrations-and-seeders)
  - [Running the Application](#running-the-application)
  - [Queue Worker](#queue-worker)
- [API Endpoints](#api-endpoints)
  - [Authentication](#authentication)
  - [Categories](#categories)
  - [Products](#products)
  - [Cart](#cart)
  - [Orders](#orders)
  - [Payments](#payments)
- [Role-Based Access Control](#role-based-access-control)
- [Testing](#testing)
- [Postman Collection](#postman-collection)

## Features

- **Authentication**: User registration, login, and logout using Laravel Sanctum for API token management.
- **Product & Category Management**: CRUD operations for products and categories.
- **Cart & Order Processing**: Add/update/remove items from the cart, create orders from the cart.
- **Payments (mocked)**: Mock payment processing for orders.
- **Role-based Access Control**: Admin and Customer roles with different access permissions.
- **Middleware**: Custom middleware for stock checking during checkout.
- **Reusable Components**: Custom service class for order calculations and a trait for common query scopes.
- **Notifications & Queues**: Email notifications for order placement using Laravel queues.
- **Caching**: Product listings are cached for performance optimization.

## Setup Instructions

### Prerequisites

Before you begin, ensure you have the following installed on your system:

- PHP >= 8.2
- Composer
- A database (e.g., MySQL, PostgreSQL, SQLite)

### Installation

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/acamasis0412/test-app
    cd test-app
    ```

2.  **Install Composer dependencies**:
    ```bash
    composer install
    ```

3.  **Create a copy of your `.env` file**:
    ```bash
    cp .env.example .env
    ```

4.  **Generate an application key**:
    ```bash
    php artisan key:generate
    ```

### Database Setup

1.  **Configure your database credentials** in the `.env` file. Replace `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` with your actual database details.

    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=your_database_name
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

2.  **Ensure your database is created** and accessible with the provided credentials.

### Running Migrations and Seeders

Run the migrations to create the necessary tables and then seed the database with initial data:

```bash
php artisan migrate:refresh --seed
```
This command will:
-   Drop all existing tables.
-   Re-run all migrations to create fresh tables, including the `users`, `categories`, `products`, `carts`, `orders`, `payments`, `personal_access_tokens`, and `jobs` tables.
-   Execute the `DatabaseSeeder` to populate the database with:
    -   2 Admin users
    -   10 Customer users
    -   5 Categories
    -   20 Products
    -   10 Carts
    -   15 Orders

### Running the Application

Start the Laravel development server:

```bash
php artisan serve
```
The application will be accessible at `http://127.0.0.1:8000`.

### Queue Worker

For order confirmation emails to be sent asynchronously, you need to run the queue worker. In a production environment, you would use a process manager like Supervisor. For local development, you can run it in a separate terminal:

```bash
php artisan queue:work
```

## API Endpoints

All API endpoints are prefixed with `/api`. For authenticated routes, include `Authorization: Bearer {YOUR_SANCTUM_TOKEN}` in the request headers.

### Authentication

-   `POST /api/register` - Register a new user.
-   `POST /api/login` - Log in a user and receive a Sanctum token.
-   `POST /api/logout` - Log out the authenticated user (requires token).
-   `GET /api/me` - Get details of the authenticated user (requires token).

### Categories

-   `GET /api/categories` - List all categories.
-   `GET /api/categories/{id}` - Get a specific category by ID.
-   `POST /api/categories` - Create a new category (Admin only, requires token).
-   `PUT /api/categories/{id}` - Update a category (Admin only, requires token).
-   `DELETE /api/categories/{id}` - Delete a category (Admin only, requires token).

### Products

-   `GET /api/products` - List all products with optional filters:
    -   `category_id`: Filter by category.
    -   `min_price`, `max_price`: Filter by price range.
    -   `search`: Search by product name.
-   `GET /api/products/{id}` - Get a specific product by ID.
-   `POST /api/products` - Create a new product (Admin only, requires token).
-   `PUT /api/products/{id}` - Update a product (Admin only, requires token).
-   `DELETE /api/products/{id}` - Delete a product (Admin only, requires token).

### Cart

-   `GET /api/cart` - Get the authenticated user's cart items (Customer only, requires token).
-   `POST /api/cart` - Add a product to the cart (Customer only, requires token).
-   `PUT /api/cart/{id}` - Update the quantity of a cart item (Customer only, requires token).
-   `DELETE /api/cart/{id}` - Remove a product from the cart (Customer only, requires token).

### Orders

-   `POST /api/orders` - Create a new order from the authenticated user's cart (Customer only, requires token, includes stock check middleware).
-   `GET /api/orders` - Get all orders for the authenticated user (Customer only, requires token).
-   `PUT /api/orders/{id}/status` - Update the status of an order (Admin only, requires token).

### Payments

-   `POST /api/orders/{id}/payment` - Process a mock payment for a specific order (Customer only, requires token).
-   `GET /api/payments/{id}` - Get details of a specific payment (Customer only, requires token).

## Role-Based Access Control

-   **Admin**: Can manage (create, read, update, delete) categories, products, and update order statuses.
-   **Customer**: Can place orders, manage their cart, and view their orders and payments.

## Testing

To run the automated tests, use the following command:

```bash
php artisan test
```

This will run all unit and feature tests.

## Postman Collection

A Postman collection with all defined API endpoints and example requests is provided separately. You can import this collection into Postman to easily test the API. The file is named postman_collection.json and is saved in the same directory as this README.

## When Testing endpoints via postman

All the default passwords for the existing users are 'password' (for both admin and customer).

Once you login via the login endpoint(/api/login), copy the token from the response and assign it to the designated variables in the request:

AUTH_TOKEN variable is for customer tokens.
ADMIN_AUTH_TOKEN variable is for admin tokens.

Once set, all endpoints are now authorized to be used.

Additionally, the email notification sent when an order is placed is being saved in storage/logs/laravel.log