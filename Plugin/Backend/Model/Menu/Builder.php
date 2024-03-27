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

namespace MagePulse\Core\Plugin\Backend\Model\Menu;

use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Config;
use Magento\Backend\Model\Menu\Filter\IteratorFactory;
use Magento\Backend\Model\Menu\ItemFactory;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Builder
{
    const MAGEPULSE_BASE_MENU = 'MagePulse_Core::magepulse_menu';
    const MAGEPULSE_BASE_MENU_ENABLED = 'magepulse_core/menu/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Menu\Config
     */
    private $menuConfig;

    /**
     * @var Menu\Filter\IteratorFactory
     */
    private $iteratorFactory;

    /**
     * @var array|null
     */
    private $magepulseItems = null;

    /**
     * @var Structure
     */
    private $configStructure;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param Config $menuConfig
     * @param IteratorFactory $iteratorFactory
     * @param Structure $configStructure
     * @param ModuleListInterface $moduleList
     * @param ObjectFactory $objectFactory
     * @param ItemFactory $itemFactory
     */
    public function __construct(
        ScopeConfigInterface        $scopeConfig,
        LoggerInterface             $logger,
        Menu\Config                 $menuConfig,
        Menu\Filter\IteratorFactory $iteratorFactory,
        Structure                   $configStructure,
        ModuleListInterface         $moduleList,
        ObjectFactory               $objectFactory,
        ItemFactory                 $itemFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->menuConfig = $menuConfig;
        $this->iteratorFactory = $iteratorFactory;
        $this->configStructure = $configStructure;
        $this->moduleList = $moduleList;
        $this->objectFactory = $objectFactory;
        $this->itemFactory = $itemFactory;
    }

    /**
     * After menu items are retrieved intercept and add our own content
     *
     * @param \Magento\Backend\Model\Menu\Builder $subject
     * @param Menu $menu
     * @return Menu
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetResult($subject, Menu $menu)
    {
        try {
            $menu = $this->menuWatcher($menu);
        } catch (\Exception $e) {
            // Fail and show original menu
        }

        return $menu;
    }

    /**
     * Menu watcher
     *
     * @param Menu $menu
     * @return Menu|null
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function menuWatcher(Menu $menu)
    {
        $this->logger->debug(__METHOD__);
        // Retrieve our MagePulse Core menu item
        $item = $menu->get(self::MAGEPULSE_BASE_MENU);
        if (!$menu) {
            $this->logger->debug(self::MAGEPULSE_BASE_MENU . ' not found, return default admin menu.');
            // Ignore if it isn't there
            return $menu;
        }

        // Disable the menu if it's turned off in the settings
        if (!$this->scopeConfig->isSetFlag(self::MAGEPULSE_BASE_MENU_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->logger->debug('Menu disabled returning default menu.');
            $menu->remove(self::MAGEPULSE_BASE_MENU);
            return $menu;
        }

        $adminMenu = $this->menuConfig->getMenu();
        $menuItems = $this->getMenuItems($adminMenu);
        $configItems = $this->getConfigItems();

        foreach ($this->getInstalledModules($configItems) as $title => $installedModule) {
            if (isset($menuItems[$installedModule])) {
                $itemsToAdd = $this->cloneMenuItems($menuItems[$installedModule], $menu);
            } else {
                $itemsToAdd = [];
            }
            $this->logger->debug('Module Title: ' . $title);

            // Check if there is a configuration area for the module
            if (isset($configItems[$installedModule]['id'])) {
                $magepulseItem = $this->generateMenuItem(
                    $installedModule . '::menuconfig',
                    $installedModule,
                    self::MAGEPULSE_BASE_MENU,
                    'adminhtml/system_config/edit/section/' . $configItems[$installedModule]['id'],
                    __('Configuration')->render()
                );

                if ($magepulseItem) {
                    $itemsToAdd[] = $magepulseItem;
                }
            }

            $parentNodeResource = '';
            foreach ($itemsToAdd as $key => $itemToAdd) {
                $itemToAdd = $itemToAdd->toArray();
                $this->logger->debug('ItemToAdd ', $itemToAdd);
                if (empty($itemToAdd['action'])) {
                    $parentNodeResource = $itemToAdd['resource'];
                    unset($itemsToAdd[$key]);
                }
            }

            if ($itemsToAdd) {
                $itemId = $installedModule . '::container';
                $moduleConfigResource = $configItems[$installedModule]['resource'] ?? $installedModule . '::config';
                /** @var \Magento\Backend\Model\Menu\Item $module */
                $module = $this->itemFactory->create(
                    [
                        'data' => [
                            'id' => $itemId,
                            'title' => $this->normalizeTitle($title),
                            'module' => $installedModule,
                            'resource' => $parentNodeResource ?: $moduleConfigResource
                        ]
                    ]
                );
                $menu->add($module, self::MAGEPULSE_BASE_MENU, 1);
                foreach ($itemsToAdd as $copy) {
                    if ($copy) {
                        $menu->add($copy, $itemId, null);
                    }
                }
            }

        }

        return $menu;
    }

    /**
     * Get menu items
     *
     * @param Menu $menu
     *
     * @return array|null
     */
    private function getMenuItems(Menu $menu)
    {
        $this->logger->debug(__METHOD__);
        if ($this->magepulseItems === null) {
            $all = $this->generateMagePulseItems($menu);
            $this->magepulseItems = [];
            foreach ($all as $item) {
                $name = explode('::', $item);
                $name = $name[0];
                if (!isset($this->magepulseItems[$name])) {
                    $this->magepulseItems[$name] = [];
                }
                $this->magepulseItems[$name][] = $item;
            }
        }

        return $this->magepulseItems;
    }

    /**
     * Generate MagePulse items
     *
     * @return array
     */
    private function generateMagePulseItems($menu)
    {
        $this->logger->debug(__METHOD__);
        $magepulse = [];
        foreach ($this->getMenuIterator($menu) as $menuItem) {
            if ($this->isCollectedNode($menuItem)) {
                $magepulse[] = $menuItem->getId();
            }
            if ($menuItem->hasChildren()) {
                foreach ($this->generateMagePulseItems($menuItem->getChildren()) as $menuChild) {
                    $magepulse[] = $menuChild;
                }
            }
        }

        return $magepulse;
    }

    /**
     * Get menu filter iterator
     *
     * @param \Magento\Backend\Model\Menu $menu
     * @return \Magento\Backend\Model\Menu\Filter\Iterator
     */
    private function getMenuIterator($menu)
    {
        $this->logger->debug(__METHOD__);
        return $this->iteratorFactory->create(['iterator' => $menu->getIterator()]);
    }

    /**
     * Is collected node
     *
     * @param $menuItem
     *
     * @return bool
     */
    private function isCollectedNode($menuItem)
    {
        $this->logger->debug(__METHOD__);
        if (strpos($menuItem->getId(), 'MagePulse') === false
            || strpos($menuItem->getId(), 'MagePulse_Core') !== false) {
            return false;
        }

        if (empty($menuItem->getAction()) || (strpos($menuItem->getAction(), 'system_config') === false)) {
            return true;
        }

        return false;
    }

    /**
     * Get config items
     *
     * @return array
     */
    private function getConfigItems()
    {
        $this->logger->debug(__METHOD__);
        $configItems = [];
        $config = $this->generateConfigItems();
        foreach ($config as $item => $section) {
            $name = explode('::', $item);
            $name = $name[0];
            $configItems[$name] = $section;
        }

        return $configItems;
    }

    /**
     * Generate config items
     *
     * @return array
     */
    private function generateConfigItems()
    {
        $this->logger->debug(__METHOD__);
        $result = [];
        $configTabs = $this->configStructure->getTabs();
        $config = $this->findResourceChildren($configTabs, 'magepulse');

        if ($config) {
            foreach ($config as $item) {
                $data = $item->getData('resource');
                if (isset($data['resource'], $data['id']) && $data['id']) {
                    $result[$data['resource']] = $data;
                }
            }
        }

        return $result;
    }


    /**
     * Find resource children
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Iterator $config
     * @param string $name
     *
     * @return \Magento\Config\Model\Config\Structure\Element\Iterator|null
     */
    private function findResourceChildren($config, $name)
    {
        $this->logger->debug(__METHOD__);
        /** @var \Magento\Config\Model\Config\Structure\Element\Tab|null $currentNode */
        $currentNode = null;
        foreach ($config as $node) {
            if ($node->getId() === $name) {
                $currentNode = $node;
                break;
            }
        }

        if ($currentNode) {
            return $currentNode->getChildren();
        }

        return null;
    }

    /**
     * Get installed modules
     *
     * @param $configItems
     *
     * @return array
     */
    private function getInstalledModules($configItems)
    {
        $this->logger->debug(__METHOD__);
        $installed = [];
        $modules = $this->moduleList->getNames();
        $dispatchResult = $this->objectFactory->create(['data' => $modules]);
        $modules = $dispatchResult->toArray();

        foreach ($modules as $moduleName) {
            if ($moduleName === 'MagePulse_Core' || strpos($moduleName, 'MagePulse_') === false) {
                continue;
            }

            $title = (isset($configItems[$moduleName]['label']) && $configItems[$moduleName]['label'])
                ? $configItems[$moduleName]['label']
                : $this->getModuleTitle($moduleName);

            $installed[$title] = $moduleName;
        }
        ksort($installed);

        return $installed;
    }

    /**
     * Get module title
     *
     * @param $name
     *
     * @return string
     */
    private function getModuleTitle($name)
    {
        $this->logger->debug(__METHOD__);
        return $result = $name;
        $module = $this->extensionsProvider->getFeedModuleData($name);
        if ($module && isset($module['name'])) {
            $result = $module['name'];
            $result = str_replace(' for Magento 2', '', $result);
        } else {
            $result = str_replace('MagePulse_', '', $result);
            $result = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $result);
        }

        return $result;
    }

    /**
     * Clone menu items
     *
     * @param $menuItems
     * @param Menu $menu
     * @return array
     */
    private function cloneMenuItems($menuItems, Menu $menu)
    {
        $this->logger->debug(__METHOD__);
        $itemsToAdd = [];
        foreach ($menuItems as $link) {
            $magepulseItem = $menu->get($link);
            if ($magepulseItem) {
                $itemData = $magepulseItem->toArray();
                if (isset($itemData['id'], $itemData['resource'], $itemData['title'])) {
                    $itemToAdd = $this->generateMenuItem(
                        $itemData['id'] . 'menu',
                        $this->getModuleFullName($itemData),
                        $itemData['resource'],
                        $itemData['action'],
                        $itemData['title']
                    );

                    if ($itemToAdd) {
                        $itemsToAdd[] = $itemToAdd;
                    }
                }
            }
        }
        return $itemsToAdd;
    }

    /**
     * Generate menu item
     *
     * @param $id
     * @param $installedModule
     * @param $resource
     * @param $url
     * @param $title
     *
     * @return bool|Menu\Item
     */
    private function generateMenuItem($id, $installedModule, $resource, $url, $title)
    {
        $this->logger->debug(__METHOD__);
        try {
            $item = $this->itemFactory->create(
                [
                    'data' => [
                        'id' => $id,
                        'title' => $title,
                        'module' => $installedModule,
                        'action' => $url,
                        'resource' => $resource
                    ]
                ]
            );
        } catch (\Exception $e) {
            $this->logger->warning($e);
            $item = false;
        }

        return $item;
    }

    /**
     * Get module full name
     *
     * @param $itemData
     *
     * @return string
     */
    private function getModuleFullName($itemData)
    {
        $this->logger->debug(__METHOD__);
        if (isset($itemData['module'])) {
            return $itemData['module'];
        } else {
            return current(explode('::', $itemData['resource']));
        }
    }

    /**
     * According to default validation rules, title can't be longer than 50 characters
     * @param string $title
     * @return string
     */
    private function normalizeTitle(string $title): string
    {
        if (mb_strlen($title) > 50) {
            $title = mb_substr($title, 0, 47) . '...';
        }

        return $title;
    }
}
