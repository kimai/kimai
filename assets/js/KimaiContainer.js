/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiContainer
 *
 * ServiceContainer for Kimai
 */

import KimaiConfiguration from './KimaiConfiguration';
import KimaiTranslation from './KimaiTranslation';
import KimaiPlugin from './KimaiPlugin';

export default class KimaiContainer {

    /**
     * Create a new Container with the given configurations and translations.
     *
     * @param {Object} configuration
     * @param {Object} translation
     */
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

    /**
     * Register a new Plugin.
     *
     * @param {KimaiPlugin} plugin
     * @returns {KimaiPlugin}
     */
    registerPlugin(plugin) {
        if (!(plugin instanceof KimaiPlugin)) {
            throw new Error('Invalid plugin given, needs to be a KimaiPlugin instance');
        }

        plugin.setContainer(this);

        this._plugins.push(plugin);

        return plugin;
    }

    /**
     * @param {string} name
     * @returns {KimaiPlugin}
     */
    getPlugin(name) {
        for (let plugin of this._plugins) {
            if (plugin.getId() !== null && plugin.getId() === name) {
                return plugin;
            }
        }
        throw new Error('Unknown plugin: ' + name);
    }

    /**
     * @returns {Array<KimaiPlugin>}
     */
    getPlugins() {
        return this._plugins;
    }

    /**
     * @returns {KimaiTranslation}
     */
    getTranslation() {
        return this._translation;
    }

    /**
     * @returns {KimaiConfiguration}
     */
    getConfiguration() {
        return this._configuration;
    }

}
