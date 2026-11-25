-- Stage 5 Paystack payment reference metadata.
ALTER TABLE orders
    ADD COLUMN payment_reference VARCHAR(80) NULL AFTER idempotency_key,
    ADD COLUMN payment_verified_at TIMESTAMP NULL DEFAULT NULL AFTER payment_reference,
    ADD UNIQUE KEY uq_orders_payment_reference (payment_reference);
