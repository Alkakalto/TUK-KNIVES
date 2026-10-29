-- MySQL 8 / MariaDB 10.6+. Import into an empty UTF-8 database.
CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 creation_key CHAR(64) NULL UNIQUE,
 number VARCHAR(40) NOT NULL UNIQUE,
 name VARCHAR(160) NOT NULL,
 description TEXT NOT NULL,
 price_cents INT UNSIGNED NOT NULL,
 category VARCHAR(100) NOT NULL,
 material VARCHAR(500) NOT NULL,
 dimensions VARCHAR(500) NOT NULL,
 availability ENUM('available','reserved','sold') NOT NULL DEFAULT 'available',
 publication ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
 version INT UNSIGNED NOT NULL DEFAULT 1,
 deleted_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX catalog (publication, availability, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE product_images (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 filename VARCHAR(80) NOT NULL,
 position INT UNSIGNED NOT NULL DEFAULT 0,
 FOREIGN KEY (product_id) REFERENCES products(id),
 INDEX product_order (product_id, position, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reference VARCHAR(40) NOT NULL UNIQUE,
 submission_key CHAR(64) NOT NULL UNIQUE,
 product_id BIGINT UNSIGNED NOT NULL,
 active_product_id BIGINT UNSIGNED NULL UNIQUE,
 product_number VARCHAR(40) NOT NULL,
 product_name VARCHAR(160) NOT NULL,
 price_cents INT UNSIGNED NOT NULL,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL,
 phone VARCHAR(50) NOT NULL DEFAULT '',
 address VARCHAR(250) NOT NULL,
 postcode VARCHAR(30) NOT NULL,
 city VARCHAR(100) NOT NULL,
 country VARCHAR(100) NOT NULL,
 payment_method ENUM('swish','agreement') NOT NULL,
 message TEXT NOT NULL,
 status ENUM('new','confirmed','awaiting_payment','paid','shipped','cancelled') NOT NULL DEFAULT 'new',
 shipping_cents INT UNSIGNED NULL,
 total_cents INT UNSIGNED NULL,
 payment_instruction TEXT NOT NULL,
 notes TEXT NOT NULL,
 version INT UNSIGNED NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (product_id) REFERENCES products(id),
 FOREIGN KEY (active_product_id) REFERENCES products(id),
 INDEX order_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE order_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 actor_id BIGINT UNSIGNED NULL,
 description VARCHAR(500) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (order_id) REFERENCES orders(id),
 FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE mail_outbox (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 kind VARCHAR(40) NOT NULL,
 recipient VARCHAR(190) NOT NULL,
 subject VARCHAR(250) NOT NULL,
 body TEXT NOT NULL,
 state ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 error_code VARCHAR(80) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 attempted_at DATETIME NULL,
 sent_at DATETIME NULL,
 FOREIGN KEY (order_id) REFERENCES orders(id),
 INDEX dispatch (state, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE rate_limits (
 bucket CHAR(64) PRIMARY KEY,
 hits INT UNSIGNED NOT NULL,
 expires_at BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE settings (
 name VARCHAR(80) PRIMARY KEY,
 value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO settings (name,value) VALUES
 ('shop_name','TUK-KNIVES'),('contact_email','tuk.knives@outlook.com'),
 ('contact_phone','0702321785'),('contact_address','Uddefors 506, Ljustorp'),
 ('owner_email',''),('swish_number',''),('swish_recipient',''),
 ('shipping_text','Vi återkommer med fraktkostnad och leveranstid innan du betalar.'),
 ('show_sold','1');
