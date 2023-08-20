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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\Module\ModuleListInterface;

abstract class ConfigProviderAbstract
{
    /**
     * @var string
     */
    protected string $pathPrefix = '/';

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var ModuleListInterface
     */
    protected ModuleListInterface $moduleList;

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var string
     */
    protected string $moduleCode;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ModuleListInterface $moduleList
     */
    public function __construct(ScopeConfigInterface $scopeConfig, ModuleListInterface $moduleList)
    {
        $this->scopeConfig = $scopeConfig;
        $this->moduleList = $moduleList;
        if ($this->pathPrefix === '/') {
            throw new \LogicException('$pathPrefix should be declared');
        }
    }

    /**
     * Return the config value
     *
     * @param string $path
     * @param int|ScopeInterface|null $storeId
     * @param string $scopeType
     * @return string
     */
    protected function getValue(
        string $path,
        $storeId = null,
        string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
    ): string
    {
        // Global store value
        if ($storeId === null && $scopeType !== ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
            return $this->scopeConfig->getValue($this->pathPrefix . $path, $scopeType, $storeId) ?? '';
        }

        if ($storeId instanceof \Magento\Framework\App\ScopeInterface) {
            $storeId = $storeId->getId();
        }
        $scopeKey = $storeId . $scopeType;

        // Store value retrieve and save to cache if not already set
        if (!isset($this->data[$path]) || !\array_key_exists($scopeKey, $this->data[$path])) {
            $this->data[$path][$scopeKey] = $this->scopeConfig->getValue(
                $this->pathPrefix . $path,
                $scopeType,
                $storeId
            ) ?? '';
        }

        // Return from prefilled cache
        return $this->data[$path][$scopeKey];
    }

    /**
     * Check if a value is set
     *
     * @param string $path
     * @param string $scopeType
     * @param int|null $storeId
     * @return bool
     */
    protected function isSetFlag(string $path, $storeId = null, string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): bool
    {
        return (bool)$this->getValue($path, $storeId, $scopeType);
    }

    /**
     * Get this modules version
     *
     * @return string
     */
    public function getExtensionVersion(): string
    {
        $moduleInfo = $this->moduleList->getOne($this->moduleCode);
        return $moduleInfo['setup_version'] ?? '0.0.1';
    }
}
