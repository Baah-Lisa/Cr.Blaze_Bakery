# Mobile Money integration plan

The application now supports a server-side Paystack initialization and callback-verification path. Without `PAYSTACK_SECRET_KEY`, it safely records a Mobile Money order as `pending` and does not mark it as paid.

## Recommended provider

Paystack supports Ghana cedi transactions and provides a `mobile_money` payment channel. Its server API uses:

- `POST https://api.paystack.co/transaction/initialize`
- `GET https://api.paystack.co/transaction/verify/{reference}`

All requests must use the secret key in an `Authorization: Bearer ...` header. The secret key must remain on the server.

## Required configuration

Set these server environment variables:

```text
PAYSTACK_SECRET_KEY=sk_test_...
PAYSTACK_PUBLIC_KEY=pk_test_...
BLAZE_PUBLIC_URL=https://your-domain.example
```

Use test keys during development and live keys only after HTTPS, webhook verification, and production testing are complete.

## Implemented flow

1. Checkout collects a valid customer email for Mobile Money.
2. The local order starts with `payment_status = 'pending'`.
3. Paystack is initialized from the server using the server-calculated GHS amount.
4. The provider reference is saved against the order.
5. The customer is redirected to the provider checkout URL.
6. [payment_callback.php](./payment_callback.php) verifies the reference server-side.
7. The callback confirms the provider response, amount, and currency.
8. The payment becomes `paid` only after successful verification.

The remaining setup is to configure real Paystack keys, HTTPS, and the public callback URL.

Never request or store a Mobile Money PIN. Do not trust a browser-supplied amount or a browser-only success message.
