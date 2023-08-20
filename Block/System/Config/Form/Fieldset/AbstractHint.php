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

namespace MagePulse\Core\Block\System\Config\Form\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use MagePulse\Core\Helper\Data;

abstract class AbstractHint extends Template implements RendererInterface
{
    /**
     * @var \MagePulse\Core\Helper\Data
     */
    protected Data $helper;

    /**
     * @var string
     */
    protected $_template = 'MagePulse_Core::system/config/fieldset/hint.phtml';

    /**
     * @var string
     */
    protected string $moduleCode = 'MagePulse_Core';

    /**
     * @var string
     */
    protected string $moduleName = 'MagePulse Core';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \MagePulse\Core\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data    $helper,
        array   $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    /**
     * Render the fieldset html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return mixed
     */
    public function render(AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * Return this modules name
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * Return this modules version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return 'v' . $this->helper->getExtensionVersion($this->moduleCode);
    }
}
