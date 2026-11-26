-- Cr. Blaze Bakery production schema.
-- Run this once against the configured MySQL database before first launch.
CREATE TABLE IF NOT EXISTS products (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) NULL,
  stock INT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
  id INT NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(100) NULL,
  address TEXT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id INT NOT NULL AUTO_INCREMENT,
  order_reference VARCHAR(32) NOT NULL,
  customer_id INT NULL,
  customer_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT NULL,
  delivery_location VARCHAR(150) NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('cod','momo') NOT NULL DEFAULT 'cod',
  payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  idempotency_key CHAR(64) NULL,
  payment_reference VARCHAR(80) NULL,
  payment_verified_at TIMESTAMP NULL DEFAULT NULL,
  order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(30) NULL DEFAULT 'Pending',
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_order_reference (order_reference),
  UNIQUE KEY uq_orders_idempotency_key (idempotency_key),
  UNIQUE KEY uq_orders_payment_reference (payment_reference),
  KEY idx_orders_phone (phone),
  KEY idx_orders_status (status),
  KEY fk_orders_customer (customer_id),
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT NOT NULL AUTO_INCREMENT,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  KEY idx_order_items_product (product_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed only missing products. Re-running this file must not reset stock or overwrite
-- product edits, customer records, or orders.
INSERT IGNORE INTO products (id, name, description, price, image, stock) VALUES
(1, 'Cookies', 'Fresh and delicious cookies', 15.00, 'cookies.jpg', 19),
(2, 'Sweets and Candies', 'Sweet treats for every occasion', 10.00, 'sweets.jpg', 30),
(3, 'Meat Pies', 'Tasty and freshly baked meat pies', 15.00, 'meat-pies.jpg', 20);
