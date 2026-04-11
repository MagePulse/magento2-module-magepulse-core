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

namespace MagePulse\Core\Block\Adminhtml\Modules;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use MagePulse\Core\Model\ModuleListing;

class Listing extends Template
{
    private ModuleListing $moduleListing;

    public function __construct(Context $context, ModuleListing $moduleListing, array $data = [])
    {
        parent::__construct($context, $data);
        $this->moduleListing = $moduleListing;
    }

    public function getInstalledModules(): array
    {
        return array_values(array_filter(
            $this->moduleListing->getModules(),
            fn($m) => in_array($m['display_state'], ['installed_active', 'installed_disabled', 'installed_no_account_data'])
        ));
    }

    public function getLicensedNotInstalled(): array
    {
        return array_values(array_filter(
            $this->moduleListing->getModules(),
            fn($m) => $m['display_state'] === 'licensed_not_installed'
        ));
    }

    public function getAvailableModules(): array
    {
        return array_values(array_filter(
            $this->moduleListing->getModules(),
            fn($m) => $m['display_state'] === 'available_to_purchase'
        ));
    }
}
