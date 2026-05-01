<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * HTTP client - thin cURL wrapper for Replicate API + binary downloads.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesHttpClient
{
    public const DEFAULT_MAX_DOWNLOAD_BYTES = 50 * 1024 * 1024;

    /** @var string */
    private $apiToken;
    /** @var int */
    private $timeout = 120;
    /** @var int */
    private $maxDownloadBytes = self::DEFAULT_MAX_DOWNLOAD_BYTES;

    /**
     * @param string|null $apiToken Override; defaults to configured Replicate key.
     */
    public function __construct($apiToken = null)
    {
        if ($apiToken === null) {
            $apiToken = (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_API_KEY);
        }
        $this->apiToken = (string) $apiToken;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;

        return $this;
    }

    public function setMaxDownloadBytes($bytes)
    {
        $this->maxDownloadBytes = max(1024, (int) $bytes);

        return $this;
    }

    /**
     * @param string $url
     * @param array $payload
     *
     * @return array{success: bool, data?: array, error?: string, http_code?: int}
     */
    public function post($url, array $payload)
    {
        return $this->request('POST', $url, json_encode($payload));
    }

    /**
     * @param string $url
     *
     * @return array
     */
    public function get($url)
    {
        return $this->request('GET', $url);
    }

    /**
     * Stream-download a remote URL to a local file.
     *
     * @param string $url
     * @param string $localPath
     *
     * @return bool
     */
    public function downloadFile($url, $localPath)
    {
        $dir = dirname($localPath);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            PrestaShopLogger::addLog('SCENES download: cannot create dir ' . $dir, 3);

            return false;
        }
        $fp = fopen($localPath, 'wb');
        if (!$fp) {
            PrestaShopLogger::addLog('SCENES download: cannot open ' . $localPath, 3);

            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'BiAiScenes/1.0 (+PrestaShop)',
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function ($_ch, $_dlTotal, $dlNow) {
                if ($dlNow > $this->maxDownloadBytes) {
                    return 1; // abort
                }

                return 0;
            },
        ]);
        curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if ($errno !== 0 || $code >= 400) {
            @unlink($localPath);
            PrestaShopLogger::addLog('SCENES download failed for ' . $url . ' - HTTP ' . $code . ' / ' . $err, 2);

            return false;
        }

        return true;
    }

    /**
     * @param string $method
     * @param string $url
     * @param string|null $body
     *
     * @return array
     */
    private function request($method, $url, $body = null)
    {
        if (!$this->apiToken) {
            return ['success' => false, 'error' => 'Replicate API key not configured', 'http_code' => 0];
        }
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: BiAiScenes/1.0 (+PrestaShop)',
        ];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);

        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return ['success' => false, 'error' => 'cURL: ' . $err, 'http_code' => $code];
        }

        $data = json_decode((string) $resp, true);
        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'data' => is_array($data) ? $data : [], 'http_code' => $code];
        }

        $errMsg = is_array($data) && isset($data['detail']) ? $data['detail'] : (is_array($data) && isset($data['error']) ? $data['error'] : ('HTTP ' . $code));

        return ['success' => false, 'error' => is_string($errMsg) ? $errMsg : json_encode($errMsg), 'http_code' => $code, 'data' => is_array($data) ? $data : []];
    }
}
