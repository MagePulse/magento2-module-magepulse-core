# MagePulse Core Magento 2 Module

[![License][ico-license]][link-license]
[![Total Downloads][ico-downloads]][link-downloads]
[![Latest Stable Version][ico-version]][link-version]

This is the core module required by MagePulse Magento 2 modules. It provides a shared admin menu under **MagePulse** in the Magento admin sidebar, and a modules listing page at **MagePulse > Modules**.

## Adding a child module to the MagePulse admin menu

The MagePulse admin menu is built automatically from any installed module whose name starts with `MagePulse_` (excluding `MagePulse_Core` itself). No extra DI configuration is needed — the menu plugin discovers child modules automatically.

### 1. Declare menu items (`etc/adminhtml/menu.xml`)

Add your module's menu items to your own `menu.xml`. The plugin collects any item whose ID starts with `MagePulse_` but is not from `MagePulse_Core`, and whose action is not a `system_config` route (config links are added automatically — see step 2).

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Backend:etc/menu.xsd">
    <menu>
        <add id="MagePulse_Example::example_menu"
             title="Example Module"
             module="MagePulse_Example"
             resource="MagePulse_Example::menu"
             action="magepulse_example/index/index"
             parent="MagePulse_Core::magepulse_menu"
             sortOrder="30"/>
    </menu>
</config>
```

The plugin will clone this item and nest it under the MagePulse top-level menu. Titles longer than 50 characters are truncated automatically.

### 2. Add a system config section (`etc/adminhtml/system.xml`) — optional

If your module has a system configuration page, assign it to the `magepulse` tab. The plugin detects any config section under that tab and automatically appends a **Configuration** link below your module's menu items.

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/system_file.xsd">
    <system>
        <section id="magepulse_example" sortOrder="20"
                 showInDefault="1" showInStore="1" showInWebsite="1">
            <label>Example Module</label>
            <tab>magepulse</tab>
            <resource>MagePulse_Example::config</resource>
            <!-- ... your groups/fields ... -->
        </section>
    </system>
</config>
```

The section's `<label>` is used as the module's display title in the menu. The section's `<resource>` must be declared in your `etc/acl.xml`.

[ico-license]: https://poser.pugx.org/MagePulse/magento2-module-core/license
[ico-downloads]: https://poser.pugx.org/MagePulse/magento2-module-core/downloads
[ico-version]: https://poser.pugx.org/MagePulse/magento2-module-core/v/stable

[link-license]: ./LICENSE.md
[link-downloads]: https://packagist.org/packages/magepulse/magento2-module-core
[link-version]: https://packagist.org/packages/magepulse/magento2-module-core
