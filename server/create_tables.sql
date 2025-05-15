-- Drop existing tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS order_products;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_products;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS rooms;

-- Create rooms table
CREATE TABLE rooms (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description VARCHAR(255)
); 

-- Create users table with added fields
CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    public_id VARCHAR(255),
    gender VARCHAR(10),
    room_id INT UNSIGNED,
    reset_token VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL 
);  

-- Create categories table
CREATE TABLE categories (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Create products table
CREATE TABLE products (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NOT NULL,
    public_id VARCHAR(255),
    category_id INT UNSIGNED,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    availability VARCHAR(255) NOT NULL DEFAULT 'available',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL
);

-- Create carts table
CREATE TABLE carts (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED,
    FOREIGN KEY (user_id) REFERENCES users(id),
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create cart_products table
CREATE TABLE cart_products (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cart_id INT UNSIGNED,
    product_id INT UNSIGNED,
    quantity INT UNSIGNED,
    FOREIGN KEY (cart_id) REFERENCES carts(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create orders table
CREATE TABLE orders (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED,
    room_id INT UNSIGNED,
    status VARCHAR(255) NOT NULL DEFAULT 'processing',
    total_price DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Create order_products table (missing from original schema)
CREATE TABLE order_products (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id INT UNSIGNED,
    product_id INT UNSIGNED,
    quantity INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
