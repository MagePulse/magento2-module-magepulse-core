<?php
/*
 * MagePulse
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MagePulse Proprietary EULA
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * https://magepulse.com/legal/magento-license/
 *
 * @category    MagePulse
 * @package     MagePulse_Core
 * @copyright   Copyright (c) MagePulse (https://magepulse.com)
 * @license     https://magepulse.com/legal/magento-license/  MagePulse Proprietary EULA
 *
 */

declare(strict_types=1);

namespace MagePulse\Core\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;

class ApiClient
{
    private const API_URL = 'https://www.magepulse.com/api/v1/modules';
    private const CACHE_LIFETIME = 3600;

    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @param Curl $curl
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        Curl $curl,
        CacheInterface $cache,
        SerializerInterface $serializer,
        ConfigProvider $configProvider
    ) {
        $this->curl = $curl;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->configProvider = $configProvider;
    }

    /**
     * Fetch and return the modules array from the MagePulse API.
     * Returns [] if no account key is set, or on any failure.
     *
     * @return array
     */
    public function getModules(): array
    {
        $accountKey = $this->configProvider->getAccountKey();

        if ($accountKey === null || $accountKey === '') {
            return [];
        }

        $cacheKey = 'magepulse_api_modules_' . md5($accountKey);
        $cached = $this->cache->load($cacheKey);

        if ($cached !== false) {
            try {
                return $this->serializer->unserialize($cached);
            } catch (\Exception $e) {
                // Fall through to re-fetch
            }
        }

        try {
            $this->curl->setTimeout(5);
            $this->curl->addHeader('X-MagePulse-Account-Key', $accountKey);
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->get(self::API_URL);

            if ($this->curl->getStatus() !== 200) {
                return [];
            }

            $body = $this->curl->getBody();
            $data = $this->serializer->unserialize($body);

            if (!isset($data['modules']) || !is_array($data['modules'])) {
                return [];
            }

            $modules = $data['modules'];

            $this->cache->save(
                $this->serializer->serialize($modules),
                $cacheKey,
                [],
                self::CACHE_LIFETIME
            );

            return $modules;
        } catch (\Exception $e) {
            return [];
        }
    }
}
