<?php

function paystack_request(string $method, string $url, ?array $payload = null): ?array
{
    $secret = getenv('PAYSTACK_SECRET_KEY') ?: '';
    if ($secret === '' || !function_exists('curl_init')) {
        return null;
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($payload !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    }
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($response === false || $status < 200 || $status >= 300) {
        return null;
    }
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

function paystack_initialize(string $email, float $amount, string $reference, string $callback_url): ?array
{
    return paystack_request('POST', 'https://api.paystack.co/transaction/initialize', [
        'email' => $email,
        'amount' => (string) round($amount * 100),
        'currency' => 'GHS',
        'channels' => ['mobile_money'],
        'reference' => $reference,
        'callback_url' => $callback_url,
    ]);
}

function paystack_verify(string $reference): ?array
{
    return paystack_request('GET', 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
}
