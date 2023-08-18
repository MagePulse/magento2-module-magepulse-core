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

namespace MagePulse\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Module\ModuleListInterface;

class Data extends AbstractHelper
{
    const DATE_INTERNAL_FORMAT = 'yyyy-MM-dd';
    const DATE_HUMAN_FORMAT = 'jS F Y';
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;
    /**
     * @var CurrencyInterface
     */
    protected $currency;
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    private $moduleList;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param ModuleListInterface $moduleList
     * @param CurrencyInterface $currency
     */
    public function __construct(
        Context             $context,
        Http                $request,
        ModuleListInterface $moduleList,
        CurrencyInterface   $currency
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->moduleList = $moduleList;
        $this->currency = $currency;
    }

    /**
     * Retrieves a modules setup version
     *
     * @param $moduleName
     *
     * @return mixed
     */
    public function getExtensionVersion($moduleName)
    {
        $moduleCode = $moduleName;
        $moduleInfo = $this->moduleList->getOne($moduleCode);
        return $moduleInfo['setup_version'];
    }

    /**
     * @return bool
     */
    public function isHome()
    {
        return ($this->request->getFullActionName() == 'cms_index_index') ? true : false;
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }

    /**
     * @param        $date
     * @param string $format
     *
     * @return string|null
     * @throws \Exception
     */
    public function formatDate($date, $format = '')
    {
        if (empty($date)) {
            return null;
        }

        if (empty($format)) {
            $format = self::DATE_HUMAN_FORMAT;
        }

        $date = (new \DateTime($date))->getTimestamp();
        return (new \DateTime())->setTimestamp($date)->format($format);
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param null $dateToCheck
     *
     * @return bool
     * @throws \Exception
     */
    public function betweenDates(\DateTime $startDate, \DateTime $endDate, $dateToCheck = null)
    {
        if (!$startDate instanceof \DateTime || !$endDate instanceof \DateTime) {
            return false;
        }

        $toCheck = (!is_null($dateToCheck)) ?
            $dateToCheck :
            new \DateTime(null, new \DateTimeZone('Europe/London'));

        return (
            $toCheck->getTimestamp() > $startDate->getTimestamp() && $toCheck->getTimestamp() < $endDate->getTimestamp()
        ) ? true : false;
    }

    /**
     * @param $code
     *
     * @return string
     */
    public function getCurrencySymbol($code)
    {
        return $this->currency->getCurrency($code)->getSymbol();
    }
}
