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

class ConfigProvider extends ConfigProviderAbstract
{
    protected string $pathPrefix = 'magepulse_core/';
    protected string $moduleCode = 'MagePulse_Core';

    public const ACCOUNT_KEY = 'account/key';

    public function getAccountKey(): ?string
    {
        $key = $this->getValue(self::ACCOUNT_KEY);
        return $key !== '' ? $key : null;
    }
}
