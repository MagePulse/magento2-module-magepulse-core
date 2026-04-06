#!/usr/bin/env node
'use strict';

const fs = require('fs').promises;
const path = require('path');

/**
 * @typedef {Object} Props
 * @property {string} [tag]     Next tag e.g. 'v1.2.3'
 * @property {string} [version] Next version e.g. '1.2.3'
 */

exports.preCommit = async function preCommit(props) {
    if (!props || (!props.version && !props.tag)) return;

    const version = (props.version || props.tag || '').replace(/^v/i, '').trim();
    if (!version) return;

    const workspace = process.env.GITHUB_WORKSPACE || process.cwd();
    const xmlPath = path.resolve(workspace, 'etc', 'module.xml');

    try {
        const content = await fs.readFile(xmlPath, 'utf8');
        const updated = content.replace(/setup_version="[^"]*"/, `setup_version="${version}"`);
        if (updated !== content) {
            await fs.writeFile(xmlPath, updated, 'utf8');
            console.log(`preCommit: updated ${xmlPath} to setup_version="${version}"`);
        }
    } catch (err) {
        console.error('preCommit: failed to update module.xml:', err && err.message ? err.message : err);
    }
};

if (require.main === module) {
    (async () => {
        const version = process.argv[2] || process.env.NEW_VERSION;
        if (!version) {
            console.error('Usage: node scripts/update-module-xml.cjs <version>');
            process.exit(2);
        }
        await exports.preCommit({ version });
    })();
}
