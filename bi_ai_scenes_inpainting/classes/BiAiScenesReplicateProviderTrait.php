<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Shared Replicate provider behaviour - prediction lifecycle, output
 * extraction, credential validation. Concrete providers configure model
 * version + input mapping.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

trait BiAiScenesReplicateProviderTrait
{
    /** @var BiAiScenesHttpClient */
    private $http;

    public function __construct(BiAiScenesHttpClient $httpClient)
    {
        $this->http = $httpClient;
    }

    public function getConfigFields()
    {
        return [
            [
                'name' => BiAiScenesConfiguration::KEY_API_KEY,
                'label' => 'Replicate API Key',
                'type' => 'password',
                'desc' => 'Your Replicate API token (shared across all Replicate-based providers).',
            ],
        ];
    }

    public function validateCredentials()
    {
        $result = $this->http->get('https://api.replicate.com/v1/account');

        if ($result['success']) {
            $username = isset($result['data']['username']) ? $result['data']['username'] : '?';

            return ['valid' => true, 'message' => 'Connected as: ' . $username];
        }

        return ['valid' => false, 'message' => isset($result['error']) ? $result['error'] : 'Unknown error'];
    }

    public function getStatus($predictionId)
    {
        $result = $this->http->get(BiAiScenesApiInterface::PREDICTIONS_URL . '/' . $predictionId);

        if (!$result['success']) {
            return ['status' => 'error', 'error' => $result['error']];
        }

        return [
            'status' => $result['data']['status'],
            'output' => isset($result['data']['output']) ? $result['data']['output'] : null,
            'error' => isset($result['data']['error']) ? $result['data']['error'] : null,
        ];
    }

    public function cancel($predictionId)
    {
        $result = $this->http->post(BiAiScenesApiInterface::PREDICTIONS_URL . '/' . $predictionId . '/cancel', []);

        return $result['success'];
    }

    public function downloadFile($url, $localPath)
    {
        return $this->http->downloadFile($url, $localPath);
    }

    /**
     * Submit a prediction with the given model version + input mapping.
     *
     * @param string $version Replicate model version SHA, or "owner/name" for latest
     * @param array  $input
     *
     * @return array
     */
    protected function submitPrediction($version, array $input)
    {
        $payload = ['input' => $input];
        // "owner/name" → use the model endpoint, version SHA → use predictions endpoint
        if (strpos($version, '/') !== false) {
            $endpoint = 'https://api.replicate.com/v1/models/' . $version . '/predictions';
        } else {
            $endpoint = BiAiScenesApiInterface::PREDICTIONS_URL;
            $payload['version'] = $version;
        }

        $response = $this->http->post($endpoint, $payload);

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'API error: ' . (isset($response['error']) ? $response['error'] : 'Unknown'),
            ];
        }

        $prediction = $response['data'];

        if (isset($prediction['status']) && $prediction['status'] === 'succeeded') {
            $imageUrl = $this->extractImageUrl($prediction);
            if ($imageUrl) {
                return [
                    'success' => true,
                    'image_url' => $imageUrl,
                    'prediction_id' => isset($prediction['id']) ? $prediction['id'] : null,
                    'raw_output' => $prediction['output'],
                ];
            }
        }

        if (isset($prediction['id'])) {
            return [
                'success' => false,
                'prediction_id' => $prediction['id'],
                'status' => isset($prediction['status']) ? $prediction['status'] : 'starting',
                'error' => null,
            ];
        }

        return ['success' => false, 'error' => 'No prediction ID in API response'];
    }

    /**
     * Extract image URL from a Replicate prediction output.
     *
     * @param array $prediction
     *
     * @return string|null
     */
    protected function extractImageUrl($prediction)
    {
        $output = isset($prediction['output']) ? $prediction['output'] : null;
        if (!$output) {
            return null;
        }

        if (is_string($output)) {
            return $output;
        }

        if (is_array($output)) {
            // Common keys
            foreach (['image', 'images', 'output', 'image_url'] as $key) {
                if (!empty($output[$key])) {
                    if (is_string($output[$key])) {
                        return $output[$key];
                    }
                    if (is_array($output[$key]) && !empty($output[$key][0]) && is_string($output[$key][0])) {
                        return $output[$key][0];
                    }
                }
            }
            // First image-like URL
            foreach ($output as $val) {
                if (is_string($val) && preg_match('/\.(png|jpe?g|webp)(\?|$)/i', $val)) {
                    return $val;
                }
            }
            // Fallback first valid URL
            foreach ($output as $val) {
                if (is_string($val) && filter_var($val, FILTER_VALIDATE_URL)) {
                    return $val;
                }
            }
        }

        return null;
    }
}
