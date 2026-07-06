<?php

/**
 * Class OpenPlantBookModule
 *
 * Wrapper for the open.plantbook.io REST API. Caches the OAuth2 bearer
 * token in cache/openplantbook_token.json (gitignored). Fetches species
 * detail with care thresholds (light, temp, humidity, soil moisture).
 *
 * The token has a 24h lifetime; we cache it and refresh on expiry or
 * 401 response.
 *
 * See wishlist.md goal #6: cache external lookups locally.
 */
class OpenPlantBookModule {
    const API_BASE = 'https://open.plantbook.io/api/v1/';
    const CONFIG_PATH = 'app/config/openplantbook.json';
    const TOKEN_CACHE_PATH = 'cache/openplantbook_token.json';
    const REQUEST_TIMEOUT_SECONDS = 15;

    /**
     * Fetch a species detail record from OpenPlantBook. Returns the raw
     * decoded JSON as an associative array.
     *
     * @param string $scientificName e.g. "Monstera deliciosa"
     * @param bool $includeCare If true, requests the free-text care fields too
     * @return array
     * @throws \Exception On config / network / API errors
     */
    public static function fetchSpecies($scientificName, $includeCare = true)
    {
        $config = static::getConfig();
        $token = static::getValidToken($config);
        $url = static::API_BASE . 'plant/detail/' . rawurlencode($scientificName) . '/?lang=en';
        if ($includeCare) {
            $url .= '&include=care';
        }

        $response = static::httpGet($url, $token);
        if ($response['status'] === 401) {
            // Token rejected. Force refresh and retry once.
            static::clearTokenCache();
            $token = static::getValidToken($config, true);
            $response = static::httpGet($url, $token);
        }

        if ($response['status'] === 404) {
            throw new \Exception("OpenPlantBook: species not found: {$scientificName}");
        }
        if ($response['status'] !== 200) {
            throw new \Exception("OpenPlantBook: HTTP {$response['status']} for {$scientificName}");
        }

        $decoded = json_decode($response['body'], true);
        if (!is_array($decoded)) {
            throw new \Exception("OpenPlantBook: malformed JSON response for {$scientificName}");
        }

        return $decoded;
    }

    /**
     * Read the credentials file. Throws a clear, actionable error if not
     * configured, so the LLM (or the CLI) can tell the user what to do.
     *
     * @return array{client_id: string, client_secret: string}
     */
    public static function getConfig()
    {
        $path = static::CONFIG_PATH;
        if (!file_exists($path)) {
            throw new \Exception(
                'OpenPlantBook credentials not configured. ' .
                'Copy app/config/openplantbook.json.example to app/config/openplantbook.json ' .
                'and fill in client_id and client_secret from https://open.plantbook.io/apikey/show/.'
            );
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \Exception("OpenPlantBook: failed to read config file at {$path}");
        }
        $config = json_decode($raw, true);
        if (!is_array($config) || !isset($config['client_id']) || !isset($config['client_secret'])) {
            throw new \Exception("OpenPlantBook: config file at {$path} is malformed (need client_id and client_secret).");
        }
        if ($config['client_id'] === 'your_client_id_here' || $config['client_secret'] === 'your_client_secret_here') {
            throw new \Exception(
                'OpenPlantBook: config file at ' . $path . ' still has placeholder credentials. ' .
                'Replace with the real client_id and client_secret from open.plantbook.io.'
            );
        }
        return $config;
    }

    /**
     * Get a valid bearer token, fetching a new one if the cached one is
     * missing or expired (or if $forceRefresh is true).
     */
    private static function getValidToken($config, $forceRefresh = false)
    {
        if (!$forceRefresh) {
            $cached = static::readTokenCache();
            if ($cached !== null && isset($cached['access_token'], $cached['expires_at']) && $cached['expires_at'] > (time() + 60)) {
                return $cached['access_token'];
            }
        }
        return static::requestNewToken($config);
    }

    private static function readTokenCache()
    {
        $path = static::TOKEN_CACHE_PATH;
        if (!file_exists($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function writeTokenCache($accessToken, $expiresInSeconds)
    {
        $path = static::TOKEN_CACHE_PATH;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $payload = json_encode([
            'access_token' => $accessToken,
            'expires_at' => time() + (int)$expiresInSeconds,
        ]);
        @file_put_contents($path, $payload);
    }

    private static function clearTokenCache()
    {
        $path = static::TOKEN_CACHE_PATH;
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    private static function requestNewToken($config)
    {
        $url = static::API_BASE . 'token/';
        $body = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, static::REQUEST_TIMEOUT_SECONDS);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('OpenPlantBook token request failed: ' . $error);
        }
        if ($status !== 200) {
            throw new \Exception("OpenPlantBook token request returned HTTP {$status}: {$response}");
        }
        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['access_token'], $data['expires_in'])) {
            throw new \Exception('OpenPlantBook token response malformed: ' . $response);
        }
        static::writeTokenCache($data['access_token'], (int)$data['expires_in']);
        return $data['access_token'];
    }

    private static function httpGet($url, $token)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        curl_setopt($ch, CURLOPT_TIMEOUT, static::REQUEST_TIMEOUT_SECONDS);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("OpenPlantBook request to {$url} failed: {$error}");
        }
        return ['status' => (int)$status, 'body' => (string)$body];
    }
}
