<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/20 下午 03:40
 */

namespace App\Models\Wallets\Databases\Services;

use App\Models\Users\Databases\Services\UserApiService;

class WalletApiRelayService
{
    private $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => config('services.walletApi.domain'),
            // 不驗證 https
            'verify_ssl' => false,
        ]);
    }

    public function getCategories()
    {
        return $this->callService('GET', '/categories')['data'];
    }

    public function getWallets($userId)
    {
        return $this->callService('GET', '/wallets', [
            'headers' => [
                'Authorization' => 'Bearer ' . app(UserApiService::class)->getUserJwtById($userId)
            ]
        ]);
    }

    /**
     * 呼叫外部服務
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return mixed
     */
    public function callService(string $method, string $url, array $options = [])
    {
        try {
            $response = $this->client->request(strtoupper($method), $url, $options);
            $statusCode = $response->getStatusCode();
            $response = json_decode($response->getBody()->getContents(), true);
            $response['code'] = $statusCode;
            $response['message'] = !empty($response['message']) ? $response['message'] : '';
            if (!empty($response['data'])) {
                $response['data'] = $this->handleResponseData($response['data'], 2);
            }
            return $response;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Exception("Service call failed: " . $e->getMessage());
        }
    }

    /**
     * 處理回傳資料
     *
     * @param array $resources
     * @param int|null $depth
     * @return array
     */
    public function handleResponseData(array $resources, ?int $depth = null)
    {
        $result = [];
        foreach ($resources as $resourceKey => $resource) {
            $resourceKey = !is_numeric($resourceKey) ? $this->camelToSnake($resourceKey) : $resourceKey; 
            if (is_array($resource) && ($depth === null || $depth > 0)) {
                $result[$resourceKey] = $this->handleResponseData($resource, $depth !== null ? $depth - 1 : null);
            } elseif (is_object($resource) && ($depth === null || $depth > 0)) {
                $result[$resourceKey] = $this->handleResponseData((array)$resource, $depth !== null ? $depth - 1 : null);
            } else {
                $result[$resourceKey] = $resource;
            }
        }
        return $result;
    }

    /**
     * 將駝峰式命名轉換為蛇形命名
     *
     * @param string $string
     * @return string
     */
    public function camelToSnake($string)
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $string));
    }
}
