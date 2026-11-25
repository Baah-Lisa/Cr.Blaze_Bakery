-- Stage 3 checkout and payment metadata migration.
-- Review this file, back up the database, then run it once in blaze_bakery.

ALTER TABLE orders
    ADD COLUMN order_reference VARCHAR(32) NULL AFTER id,
    ADD COLUMN customer_id INT NULL AFTER order_reference,
    ADD COLUMN payment_method ENUM('cod', 'momo') NOT NULL DEFAULT 'cod' AFTER total_amount,
    ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending' AFTER payment_method,
    ADD COLUMN delivery_location VARCHAR(150) NULL AFTER address,
    ADD COLUMN idempotency_key CHAR(64) NULL AFTER payment_status;

UPDATE orders
SET order_reference = CONCAT('LEGACY-', id)
WHERE order_reference IS NULL;

ALTER TABLE orders
    MODIFY order_reference VARCHAR(32) NOT NULL,
    ADD UNIQUE KEY uq_orders_order_reference (order_reference),
    ADD UNIQUE KEY uq_orders_idempotency_key (idempotency_key),
    ADD KEY idx_orders_phone (phone),
    ADD KEY idx_orders_status (status),
    ADD CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON UPDATE CASCADE ON DELETE SET NULL;

