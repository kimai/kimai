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

        if (!hasEntries) {
            return;
        }

        this._replaceInNode(this._menu, entries[0]);

        /** @type {KimaiActiveRecordsDuration} DURATION */
        const DURATION = this.getContainer().getPlugin('timesheet-duration');
        DURATION.updateRecords();
    }

    _replaceInNode(node, timesheet) {
        const date = this.getDateUtils();
        const allReplacer = node.querySelectorAll('[data-replacer]');
        for (let node of allReplacer) {
            const replacerName = node.dataset['replacer'];
            if (replacerName === 'url') {
                node.href = this.attributes['href'].replace('000', timesheet.id);
            } else if (replacerName === 'activity') {
                node.innerText = timesheet.activity.name;
            } else if (replacerName === 'project') {
                node.innerText = timesheet.project.name;
            } else if (replacerName === 'customer') {
                node.innerText = timesheet.project.customer.name;
            } else if (replacerName === 'duration') {
                node.dataset['since'] = timesheet.begin;
                node.innerText = date.formatDuration(timesheet.duration);
            }
        }

        return node;
    }

    reloadActiveRecords() {
        /** @type {KimaiAPI} API */
        const API = this.getContainer().getPlugin('api');

        API.get(this.attributes['api'], {}, (result) => {
            this._setEntries(result);
        });
    }

}
