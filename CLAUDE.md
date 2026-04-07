# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Module Overview

This is `MagePulse_Core` — a Magento 2 module that acts as the shared framework for all MagePulse extensions. It provides:
- A centralized "MagePulse" admin menu that other MagePulse modules register into
- An admin page listing all installed and available MagePulse modules
- API integration with magepulse.com to fetch licensed/available modules
- Base classes (`ConfigProviderAbstract`) that other MagePulse modules extend

**Package**: `magepulse/magento2-module-core`  
**Namespace**: `MagePulse\Core\`

## Commands

### Testing & Quality (via CI — run locally using equivalent tools)

```bash
# Unit tests
vendor/bin/phpunit

# Static analysis
vendor/bin/phpstan analyse

# Mess Detector
vendor/bin/phpmd . xml phpmd.xml

# Coding Standards (Magento2 standard, severity 10)
vendor/bin/phpcs --standard=Magento2 --severity=10
```

The CI pipeline (`.github/workflows/main.yml`) installs a fresh Magento 2.4.6-p1 instance to run all checks. There is no local Docker setup — tests require a Magento environment.

### Releases

Releases are automated via `.github/workflows/release.yml` on push to `main`. The workflow:
1. Runs `scripts/update-module-xml.cjs` to sync the version in `etc/module.xml` with `composer.json`
2. Generates changelog via conventional commits
3. Creates a GitHub release

## Architecture

### Extension Points for Child Modules

Other MagePulse modules integrate with Core via two mechanisms:

**1. Module Registry** — Child modules declare themselves in their `di.xml`:
```xml
<type name="MagePulse\Core\Model\ModuleRegistry">
    <arguments>
        <argument name="modules" xsi:type="array">
            <item name="ChildModule_Name" xsi:type="string">MagePulse_ChildModuleName</item>
        </argument>
    </arguments>
</type>
```

**2. ConfigProvider base class** — Child modules extend `ConfigProviderAbstract`, setting `$pathPrefix` and `$moduleCode` to get a config reader with built-in caching.

### Key Models

- **`Model/ModuleRegistry.php`** — Holds the registry of installed MagePulse modules (populated by child modules via DI). Returns enabled status via Magento's Module Manager.
- **`Model/ModuleListing.php`** — Merges local registry data with API data to produce display states: `installed_active`, `installed_disabled`, `licensed_not_installed`, `available_to_purchase`, `installed_no_account_data`.
- **`Model/ApiClient.php`** — Calls `https://www.magepulse.com/api/v1/modules` with the account key header `X-MagePulse-Account-Key`. Caches for 3600 seconds. Returns empty array when no key is configured or the API fails.
- **`Model/ConfigProvider.php`** / **`Model/ConfigProviderAbstract.php`** — Config reader base. `ConfigProvider` reads `magepulse_core/account/key` for the API account key.

### Admin Menu Plugin

**`Plugin/Backend/Model/Menu/Builder.php`** intercepts `Magento\Backend\Model\Menu\Builder::getResult()` to dynamically inject MagePulse module menu items. It:
- Collects menu items from `MagePulse_Core::magepulse_menu` in the compiled menu
- Adds config links for modules that declare system config
- Respects the `magepulse_core/menu/enabled` config flag
- Truncates titles to 50 characters

### Logging

A virtual logger writes to `/var/log/magepulse/core.log` (configured in `etc/di.xml`).

### System Configuration

Admin panel path: **Stores > Configuration > MagePulse Extensions > MagePulse Core**

- `magepulse_core/account/key` — Account Key (website scope locked to default)
- `magepulse_core/menu/enabled` — Show/hide MagePulse menu (default: enabled)
