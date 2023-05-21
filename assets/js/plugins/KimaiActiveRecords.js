/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiActiveRecords: responsible to display the users active records
 */

import KimaiPlugin from '../KimaiPlugin';

export default class KimaiActiveRecords extends KimaiPlugin {

    constructor()
    {
        super();
        this._selector = '.ticktac-menu';
        this._selectorEmpty = '.ticktac-menu-empty';
        this._favIconUrl = null;
    }

    /**
     * @returns {string}
     */
    getId()
    {
        return 'active-records';
    }

    init()
    {
        // the menu can be hidden if user has no permissions to see it
        if (document.querySelector(this._selector) === null) {
            return;
        }

        const handleUpdate = () => {
            this.reloadActiveRecords();
        };

        document.addEventListener('kimai.timesheetUpdate', handleUpdate);
        document.addEventListener('kimai.timesheetDelete', handleUpdate);
        document.addEventListener('kimai.activityUpdate', handleUpdate);
        document.addEventListener('kimai.activityDelete', handleUpdate);
        document.addEventListener('kimai.projectUpdate', handleUpdate);
        document.addEventListener('kimai.projectDelete', handleUpdate);
        document.addEventListener('kimai.customerUpdate', handleUpdate);
        document.addEventListener('kimai.customerDelete', handleUpdate);

        // -----------------------------------------------------------------------
        // handle duration in the visible UI
        this._updateBrowserTitle = !!this.getConfiguration('updateBrowserTitle');

        const handle = () => {
            this._updateDuration();
        };
        this._updatesHandler = setInterval(handle, 10000);
        document.addEventListener('kimai.timesheetUpdate', handle);
        document.addEventListener('kimai.reloadedContent', handle);
    }

    // TODO we could unregister all handler and listener
    // _unregisterHandler() {
    //     clearInterval(this._updatesHandler);
    // }

    /**
     * Updates the duration of all running entries, both in the ticktac menus and in the listing pages.
     *
     * @private
     */
    _updateDuration()
    {
        // needs to search in document, to find all running entries, both in "ticktac" and listing pages
        const activeRecords = document.querySelectorAll('[data-since]:not([data-since=""])');

        if (this._updateBrowserTitle) {
            this._changeFavicon(activeRecords.length > 0);
        }

        if (activeRecords.length === 0) {
            if (this._updateBrowserTitle) {
                if (document.body.dataset['title'] === undefined) {
                    this._updateBrowserTitle = false;
                } else {
                    document.title = document.body.dataset['title'];
                }
            }
            return;
        }

        const DATE = this.getDateUtils();
        let durations = [];

        for (const record of activeRecords) {
            const duration = DATE.formatDuration(record.dataset['since']);
            // only use the ones from the menu for the title
            if (record.dataset['replacer'] !== undefined && record.dataset['title'] !== null && duration !== '?') {
                durations.push(duration);
            }
            // but update all on the page (running entries in list pages)
            record.textContent = duration;
        }

        if (durations.length === 0) {
            return;
        }

        if (this._updateBrowserTitle) {
            // only show the first found record, even if we have more
            document.title = durations.shift();
        }
    }

    /**
     * Adapts the ticktac menus according to the given entries (amount and duration).
     * Does not influence listing pages, as those refresh themselves.
     *
     * @param {array} entries
     * @private
     */
    _setEntries(entries)
    {
        const hasEntries = entries.length > 0;

        // these contain the "start" button
        for (let menuEmpty of document.querySelectorAll(this._selectorEmpty)) {
            menuEmpty.style.display = !hasEntries ? 'inline-block' : 'none';
        }

        // and they contain the "stop" button
        for (let menu of document.querySelectorAll(this._selector)) {
            menu.style.display = hasEntries ? 'inline-block' : 'none';
            if (!hasEntries) {
                // make sure that template entries in the menu are removed, otherwise they
                // might still be shown in the browsers title
                for (let record of menu.querySelectorAll('[data-since]')) {
                    record.dataset['since'] = '';
                }
            }

            const stop = menu.querySelector('.ticktac-stop');

            if (!hasEntries) {
                if (stop) {
                    stop.accesskey = null;
                }
                continue;
            }

            if (stop) {
                stop.accesskey = 's';
            }
            this._replaceInNode(menu, entries[0]);
        }

        this._updateDuration();
    }

    /**
     * @param {HTMLElement} node
     * @param {object} timesheet
     * @private
     */
    _replaceInNode(node, timesheet)
    {
        const date = this.getDateUtils();
        const allReplacer = node.querySelectorAll('[data-replacer]');
        for (let link of allReplacer) {
            const replacerName = link.dataset['replacer'];
            if (replacerName === 'url') {
                link.dataset['href'] = node.dataset['href'].replace('000', timesheet.id);
            } else if (replacerName === 'activity') {
                link.innerText = timesheet.activity.name;
            } else if (replacerName === 'project') {
                link.innerText = timesheet.project.name;
            } else if (replacerName === 'customer') {
                link.innerText = timesheet.project.customer.name;
            } else if (replacerName === 'duration') {
                link.dataset['since'] = timesheet.begin;
                link.innerText = date.formatDuration(timesheet.duration);
            }
        }
    }

    reloadActiveRecords()
    {
        /** @type {KimaiAPI} API */
        const API = this.getContainer().getPlugin('api');

        // TODO using the first found "ticktac" menu is working, but can be done better
        const apiUrl = document.querySelector(this._selector).dataset['api'];

        API.get(apiUrl, {}, (result) => {
            this._setEntries(result);
        });
    }

    /**
     * @param {boolean} running
     * @private
     */
    _changeFavicon(running)
    {
        const canvas = document.createElement('canvas');
        const orig = document.getElementById('favicon');
        if (this._favIconUrl === null) {
            this._favIconUrl = orig.href;
        }
        const link = orig.cloneNode(true);

        if (canvas.getContext && link) {
            const ratio = window.devicePixelRatio;
            const img = document.createElement('img');
            canvas.height = canvas.width = 16 * ratio;
            img.onload = function () {
                const ctx = canvas.getContext('2d');
                ctx.drawImage(this, 0, 0, canvas.width, canvas.height);
                if (running) {
                    const width = 5.5 * ratio;
                    ctx.fillStyle = 'rgb(182,57,57)';
                    ctx.fillRect((canvas.width / 2) - (width / 2), (canvas.height / 2) - (width / 2), width, width);
                }
                link.href = canvas.toDataURL('image/png');
                orig.remove();
                document.head.appendChild(link);
            };
            img.src = this._favIconUrl;
        }
    }
}
