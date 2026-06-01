CREATE DATABASE IF NOT EXISTS findywear;
USE findywear;

-- Users Table (Customer, Shop Owner, Admin)
CREATE TABLE userss (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    role ENUM('customer', 'shop_owner','admin') DEFAULT 'customer',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    address TEXT,
    profile_image VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Shops Table
CREATE TABLE shopss (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    shop_name VARCHAR(150) NOT NULL,
    description TEXT,
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    phone VARCHAR(15),
    shop_image VARCHAR(255) DEFAULT 'default_shop.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES userss(id)
);

-- Products Table
CREATE TABLE productss (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    category VARCHAR(100),
    size VARCHAR(50),
    color VARCHAR(50),
    image VARCHAR(255) DEFAULT 'default_product.png',
    status ENUM('available', 'out_of_stock') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shopss(id)
);

-- Orders Table
CREATE TABLE orderss (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    shop_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_type ENUM('cod', 'online') DEFAULT 'cod',
    payment_status ENUM('pending','paid','refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'accepted','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
    delivery_address TEXT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES userss(id),
    FOREIGN KEY (shop_id) REFERENCES shopss(id)
);

-- Order Items Table
CREATE TABLE order_itemss (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orderss(id),
    FOREIGN KEY (product_id) REFERENCES productss(id)
);

-- Order Tracking Table
CREATE TABLE order_trackings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orderss(id)
);

-- Returns Table
CREATE TABLE returnss (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected','refunded') DEFAULT 'pending',
    refund_amount DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orderss(id),
    FOREIGN KEY (customer_id) REFERENCES userss(id)
);

-- Cart Table
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES userss(id),
    FOREIGN KEY (product_id) REFERENCES productss(id)
);

-- Notifications Table
CREATE TABLE notificationss (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES userss(id)
);

-- Admin user insert (password: admin123)
INSERT INTO userss (name,email,password,role) VALUES ('Admin','admin@findywearce.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin');
