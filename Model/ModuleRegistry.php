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

use Magento\Framework\Module\Manager;

class ModuleRegistry
{
    /**
     * @var array
     */
    private array $modules;

    /**
     * @var Manager
     */
    private Manager $moduleManager;

    /**
     * @param array $modules
     * @param Manager $moduleManager
     */
    public function __construct(array $modules, Manager $moduleManager)
    {
        $this->modules = $modules;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Returns each locally registered MagePulse module with its enabled state.
     *
     * @return array
     */
    public function getModules(): array
    {
        $result = [];

        foreach ($this->modules as $composerPackage => $moduleData) {
            $moduleName = $moduleData['module_name'] ?? '';
            $isEnabled = $moduleName !== '' && $this->moduleManager->isEnabled($moduleName);

            $result[] = [
                'composer_package' => $composerPackage,
                'module_name'      => $moduleName,
                'name'             => $moduleData['name'] ?? '',
                'description'      => $moduleData['description'] ?? '',
                'version'          => $moduleData['version'] ?? '',
                'is_installed'     => true,
                'is_enabled'       => $isEnabled,
            ];
        }

        return $result;
    }
}
