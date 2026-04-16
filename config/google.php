<?php
declare(strict_types=1);

function google_client_id(): string
{
    return google_oauth_env_value('GOOGLE_CLIENT_ID');
}

function google_client_secret(): string
{
    return google_oauth_env_value('GOOGLE_CLIENT_SECRET');
}

function google_oauth_env_value(string $key): string
{
    $value = trim((string) (env($key, '') ?? ''));
    $placeholderValues = [
        '',
        'your-google-client-id',
        'your-google-client-secret',
        'your-google-redirect-uri',
    ];

    return in_array(strtolower($value), $placeholderValues, true) ? '' : $value;
}

function google_redirect_uri(): string
{
    $configuredUrl = trim((string) (env('GOOGLE_REDIRECT_URI', '') ?? ''));
    $detectedUrl = base_url('auth/google_callback.php');

    if ($configuredUrl === '') {
        return $detectedUrl;
    }

    $configuredHost = url_host_name($configuredUrl);
    $detectedHost = url_host_name($detectedUrl);

    if (
        is_local_host_name($configuredHost)
        || ($configuredHost !== '' && $detectedHost !== '' && $configuredHost !== $detectedHost)
    ) {
        return $detectedUrl;
    }

    return $configuredUrl;
}

function google_configuration_is_ready(): bool
{
    return google_client_id() !== '' && google_client_secret() !== '' && google_redirect_uri() !== '';
}

function google_authorization_url(): string
{
    if (!google_configuration_is_ready()) {
        throw new RuntimeException('Google OAuth is not configured.');
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;

    $parameters = [
        'client_id' => google_client_id(),
        'redirect_uri' => google_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'select_account',
        'state' => $state,
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($parameters);
}

function google_exchange_code_for_tokens(string $code): array
{
    return google_http_request(
        'POST',
        'https://oauth2.googleapis.com/token',
        [
            'code' => $code,
            'client_id' => google_client_id(),
            'client_secret' => google_client_secret(),
            'redirect_uri' => google_redirect_uri(),
            'grant_type' => 'authorization_code',
        ]
    );
}

function google_fetch_user_profile(string $accessToken): array
{
    return google_http_request(
        'GET',
        'https://www.googleapis.com/oauth2/v3/userinfo',
        [],
        [
            'Authorization: Bearer ' . $accessToken,
        ]
    );
}

function google_http_request(string $method, string $url, array $fields = [], array $headers = []): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('The PHP cURL extension is required for Google login.');
    }

    $method = strtoupper($method);
    $ch = curl_init();

    if ($ch === false) {
        throw new RuntimeException('Unable to initialize cURL.');
    }

    $defaultHeaders = [
        'Accept: application/json',
    ];

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
    ];

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($fields);
        $options[CURLOPT_HTTPHEADER] = array_merge(
            $defaultHeaders,
            ['Content-Type: application/x-www-form-urlencoded'],
            $headers
        );
    } elseif ($method === 'GET' && $fields !== []) {
        $options[CURLOPT_URL] = $url . '?' . http_build_query($fields);
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    if ($response === false) {
        $message = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Google request failed: ' . $message);
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Google returned an invalid response.');
    }

    if ($status >= 400) {
        throw new RuntimeException('Google request was rejected.');
    }

    return $decoded;
}
