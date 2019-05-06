/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiCore
 *
 * ServiceContainer for Kimai
 */

import KimaiConfiguration from './KimaiConfiguration';
import KimaiTranslation from './KimaiTranslation';
import KimaiPlugin from './KimaiPlugin';

export default class KimaiCore {

    constructor(configuration, translation) {
        if (!(configuration instanceof KimaiConfiguration)) {
            throw new Error('Configuration needs to a KimaiConfiguration instance');
        }
        this._configuration = configuration;

        if (!(translation instanceof KimaiTranslation)) {
            throw new Error('Configuration needs to a KimaiTranslation instance');
        }
        this._translation = translation;
        this._plugins = [];
    }

    registerPlugin(plugin) {
        if (!(plugin instanceof KimaiPlugin)) {
            throw new Error('Invalid plugin given, needs to be a KimaiPlugin instance');
        }

        plugin.setCore(this);

        this._plugins.push(plugin);

        return plugin;
    }

    getPlugin(name) {
        for (let plugin of this._plugins) {
            if (plugin.getId() === name) {
                return plugin;
            }
        }
        throw new Error('Unknown plugin: ' + name);
    }

    getPlugins() {
        return this._plugins;
    }

    getTranslation() {
        return this._translation;
    }

    getConfiguration() {
        return this._configuration;
    }

}
