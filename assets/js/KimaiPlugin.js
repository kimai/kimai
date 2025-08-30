/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiPlugin: base class for all plugins
 */

import KimaiContainer from "./KimaiContainer";

export default class KimaiPlugin {

    /**
     * Overwrite this method to initialize your plugin.
     *
     * It is called AFTER setContainer() and AFTER DOMContentLoaded was fired.
     * You don't have access to the container before this method!
     */
    init() {
    }

    /**
     * If you return an ID, you indicate that your plugin can be used by other plugins.
     *
     * @returns {string|null}
     */
    getId() {
        return null;
    }

    /**
     * @param {KimaiContainer} core
     */
    setContainer(core) {
        if (!(core instanceof KimaiContainer)) {
            throw new Error('Plugin was given an invalid KimaiContainer');
        }
        this._core = core;
    }

    /**
     * This function returns null, if you call it BEFORE init().
     *
     * @returns {KimaiContainer}
     */
    getContainer() {
        return this._core;
    }

    /**
     * @param {string} name
     * @returns {(string|number|boolean)}
     */
    getConfiguration(name) {
        return this.getContainer().getConfiguration().get(name);
    }

    /**
     * @return {KimaiConfiguration}
     */
    getConfigurations() {
        return this.getContainer().getConfiguration();
    }

    /**
     * @returns {KimaiDateUtils}
     */
    getDateUtils() {
        return this.getPlugin('date');
    }

    /**
     * @param {string} name
     * @returns {KimaiPlugin}
     */
    getPlugin(name) {
        return this.getContainer().getPlugin(name);
    }

    /**
     * @returns {KimaiTranslation}
     */
    getTranslation() {
        return this.getContainer().getTranslation();
    }

    /**
     * @param {string} name
     * @returns {string}
     */
    translate(name) {
        return this.getTranslation().get(name);
    }

    /**
     * @param {string} title
     * @returns {string}
     */
    escape(title) {
        return this.getPlugin('escape').escapeForHtml(title);
    }

    /**
     * @param {string} name
     * @param {string|null} details
     */
    trigger(name, details = null) {
        this.getPlugin('event').trigger(name, details);
    }

    /**
     * @param {string} url
     * @param {object} options
     * @returns {Promise<Response>}
     */
    fetch(url, options = {}) {
        return this.getPlugin('fetch').fetch(url, options);
    }

    /**
     * @param {HTMLFormElement} form
     * @param {object} options
     * @param {string|null} url
     * @returns {Promise<Response>}
     */
    fetchForm(form, options = {}, url = null) {
        url = url || form.getAttribute('action');
        const method = form.getAttribute('method').toUpperCase();

        if (method === 'GET') {
            const data = this.getPlugin('form').convertFormDataToQueryString(form, {}, true);
            // TODO const data = new URLSearchParams(new FormData(form)).toString();
            url = url + (url.includes('?') ? '&' : '?') + data;
            options = {...{method: 'GET'}, ...options};
        } else if (method === 'POST') {
            options = {...{
                method: 'POST',
                body: new FormData(form)
            }, ...options};
        }

        return this.fetch(url, options);
    }

    /**
     * Check if the current device is a mobile device (targeting the bootstrip xs breakpoint size).
     *
     * @returns {boolean}
     */
    isMobile() {
        const width = Math.max(
            document.documentElement.clientWidth,
            window.innerWidth || 0
        );

        return width < 576;
    }
}
