# MeyDa Collection - Enhanced PHP + MySQL E-commerce Platform

## Overview
MeyDa Collection is a modern e-commerce platform built with PHP and MySQL, featuring a sleek dark-themed interface with orange accents. This enhanced platform allows customers to browse products, add them to cart, and complete purchases through a streamlined checkout process.

## Features
- Responsive design with modern UI/UX
- Secure authentication system (customers and staff)
- Shopping cart functionality
- Product filtering by categories
- Admin panel for managing products, users, and reports
- Database-driven architecture with proper normalization

## Recent Security & Performance Improvements

### 1. Database Configuration Security
- Fixed incorrect database host configuration (was using a web URL instead of host address)
- Enhanced security with random token generation for admin key
- Added proper error handling for database connections

### 2. Session Management
- Improved session security with proper destruction and regeneration
- Fixed session fixation vulnerabilities
- Enhanced logout process to properly clear all session data

### 3. Input Validation & Sanitization
- Maintained existing XSS prevention with `htmlspecialchars()`
- Added CSRF protection mechanisms
- Improved SQL injection prevention with prepared statements

### 4. Authentication System
- Enhanced password hashing with PHP's native functions
- Improved session handling for customer and staff accounts
- Added proper role-based access controls

## Performance Improvements

### 1. CSS Optimizations
- Improved container centering for better responsive design
- Added font smoothing for better text rendering
- Optimized selectors for better performance
- Fixed product image sizing for consistent layouts

### 2. JavaScript Enhancements
- Improved cart notification system with fade animations
- Enhanced cart count update functionality
- Fixed redundant carousel implementations

### 3. Product Card Layout
- Fixed image aspect ratios for consistent presentation
- Added text truncation for product descriptions
- Improved button positioning and styling
- Enhanced accessibility features

## Files Added/Modified:
- `meyda_schema.sql` - DDL: 7 tables + constraints
- `meyda_seed.sql` - DML: small seed data (limited products)
- `meyda_privileges.sql` - DCL examples (create user / grant)
- `config.php` - Enhanced PDO database config (edit before deploy)
- `index.php` - Enhanced PHP storefront (products, cart, checkout)
- `cart.php` - Shopping cart functionality
- `auth.php` - Authentication and session management with security enhancements
- `styles.css` - Enhanced neutral styling, no gradients, few images
- `product-card.css` - Product card specific styles
- `cart-styles.css` - Shopping cart specific styles

- `HeroCard.php` - Hero section component
- `_footer.php` - Reusable footer component

Requirements
- PHP 7.4+ (8.x recommended) with `pdo_mysql` enabled
- MySQL 5.7+ or MariaDB equivalent

## Quick Deploy (Local Development)

1. **Database Setup**
   - Create a MySQL database for the application
   - Update `config.php` with your database credentials
   - Import `meyda_schema.sql` and `meyda_seed.sql` to initialize the database schema and sample data

2. **Configuration**
   - Edit `config.php` to match your environment settings
   - Set appropriate database credentials
   - Configure the database host (should be a hostname, not URL)

3. **Initial Access**
   - Visit the site in your browser
   - Use the default admin credentials to access the admin panel
   - Change the default password immediately

## Security Notes
- Default passwords in `meyda_seed.sql` should be replaced with secure hashes using `password_hash()` in PHP.
- Do not commit real DB credentials to source control.
- The application now includes CSRF protection, input validation, and requires HTTPS in production.
- Enhanced authentication with proper session management.

## Deployment Requirements
- PHP 7.4+ (8.x recommended) with `pdo_mysql`, `openssl`, and `json` extensions enabled
- MySQL 5.7+ or MariaDB equivalent
- HTTPS-enabled web server for production deployment
- Proper file permissions to protect sensitive configuration files
