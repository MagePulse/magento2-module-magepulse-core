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

class ModuleListing
{
    /**
     * @var ModuleRegistry
     */
    private ModuleRegistry $moduleRegistry;

    /**
     * @var ApiClient
     */
    private ApiClient $apiClient;

    /**
     * @param ModuleRegistry $moduleRegistry
     * @param ApiClient $apiClient
     */
    public function __construct(ModuleRegistry $moduleRegistry, ApiClient $apiClient)
    {
        $this->moduleRegistry = $moduleRegistry;
        $this->apiClient = $apiClient;
    }

    /**
     * Returns a merged list of local and API module data with resolved display_state.
     *
     * Display state resolution:
     *  - Local (enabled) + API licensed    => installed_active
     *  - Local (disabled) + API licensed   => installed_disabled
     *  - Not local + API licensed          => licensed_not_installed
     *  - Not local + API available         => available_to_purchase
     *  - Local + not in API               => installed_no_account_data
     *
     * @return array
     */
    public function getModules(): array
    {
        $localModules = [];
        foreach ($this->moduleRegistry->getModules() as $module) {
            $localModules[$module['composer_package']] = $module;
        }

        $apiModules = [];
        foreach ($this->apiClient->getModules() as $module) {
            $apiModules[$module['composer_package']] = $module;
        }

        $result = [];
        $processed = [];

        // Process modules that appear in both local and API sets, or only in API
        foreach ($apiModules as $composerPackage => $apiModule) {
            $localModule = $localModules[$composerPackage] ?? null;
            $apiStatus = $apiModule['status'] ?? '';

            if ($localModule !== null) {
                // Local + API
                if ($localModule['is_enabled']) {
                    $displayState = 'installed_active';
                } else {
                    $displayState = 'installed_disabled';
                }
                $version = $localModule['version'];
                $name = $localModule['name'] !== '' ? $localModule['name'] : ($apiModule['name'] ?? '');
                $description = $localModule['description'] !== '' ? $localModule['description'] : ($apiModule['description'] ?? '');
            } else {
                // API only
                if ($apiStatus === 'licensed') {
                    $displayState = 'licensed_not_installed';
                } else {
                    $displayState = 'available_to_purchase';
                }
                $version = '';
                $name = $apiModule['name'] ?? '';
                $description = $apiModule['description'] ?? '';
            }

            $result[] = [
                'composer_package' => $composerPackage,
                'name'             => $name,
                'description'      => $description,
                'display_state'    => $displayState,
                'version'          => $version,
                'latest_version'   => $apiModule['latest_version'] ?? '',
                'install_command'  => $apiModule['install_command'] ?? '',
                'docs_url'         => $apiModule['docs_url'] ?? '',
                'changelog_url'    => $apiModule['changelog_url'] ?? '',
                'purchase_url'     => $apiModule['purchase_url'] ?? null,
            ];

            $processed[$composerPackage] = true;
        }

        // Process local modules not found in the API
        foreach ($localModules as $composerPackage => $localModule) {
            if (isset($processed[$composerPackage])) {
                continue;
            }

            $result[] = [
                'composer_package' => $composerPackage,
                'name'             => $localModule['name'],
                'description'      => $localModule['description'],
                'display_state'    => 'installed_no_account_data',
                'version'          => $localModule['version'],
                'latest_version'   => '',
                'install_command'  => '',
                'docs_url'         => '',
                'changelog_url'    => '',
                'purchase_url'     => null,
            ];
        }

        return $result;
    }
}
