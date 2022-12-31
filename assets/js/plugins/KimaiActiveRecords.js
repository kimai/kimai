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

    constructor(selector, selectorEmpty) {
        super();
        this._selector = selector;
        this._selectorEmpty = selectorEmpty;
    }

    getId() {
        return 'active-records';
    }

    init() {
        this._menu = document.querySelector(this._selector);

        // the menu can be hidden if user has no permissions to see it
        if (this._menu === null) {
            return;
        }

        this.attributes = this._menu.dataset;

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
        this._updateDuration();
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

    _updateDuration() {
        const activeRecords = this._menu.querySelectorAll('[data-since]:not([data-since=""])');

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

        if (!this._updateBrowserTitle) {
            return;
        }

        let title = durations.shift();
        for (const duration of durations.slice(0, 2)) {
            title += ' | ' + duration;
        }
        document.title = title;
    }

    _setEntries(entries) {
        const hasEntries = entries.length > 0;

        this._menu.style.display = hasEntries ? 'inline-block' : 'none';
        if (!hasEntries) {
            // make sure that template entries in the menu are removed, otherwise they
            // might still be shown in the browsers title
            for (let record of this._menu.querySelectorAll('[data-since]')) {
                record.dataset['since'] = '';
            }
        }

        const menuEmpty = document.querySelector(this._selectorEmpty);
        if (menuEmpty !== null) {
            menuEmpty.style.display = !hasEntries ? 'inline-block' : 'none';
        }

        const stop = this._menu.querySelector('.ticktac-stop');

        if (!hasEntries) {
            if (stop) {
                stop.accesskey = null;
            }
            return;
        }

        if (stop) {
            stop.accesskey = 's';
        }
        this._replaceInNode(this._menu, entries[0]);
        this._updateDuration();
    }

    _replaceInNode(node, timesheet) {
        const date = this.getDateUtils();
        const allReplacer = node.querySelectorAll('[data-replacer]');
        for (let link of allReplacer) {
            const replacerName = link.dataset['replacer'];
            if (replacerName === 'url') {
                link.href = this.attributes['href'].replace('000', timesheet.id);
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

    reloadActiveRecords() {
        /** @type {KimaiAPI} API */
        const API = this.getContainer().getPlugin('api');

        API.get(this.attributes['api'], {}, (result) => {
            this._setEntries(result);
        });
    }

}
