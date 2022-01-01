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
        this.selector = selector;
        this.selectorEmpty = selectorEmpty;
    }

    getId() {
        return 'active-records';
    }

    init() {
        this.menu = document.querySelector(this.selector);

        // the menu can be hidden if user has no permissions to see it
        if (this.menu === null) {
            return;
        }

        this.attributes = this.menu.dataset;

        const self = this;
        const handle = function() { self.reloadActiveRecords(); };

        document.addEventListener('kimai.timesheetUpdate', handle);
        document.addEventListener('kimai.timesheetDelete', handle);
        document.addEventListener('kimai.activityUpdate', handle);
        document.addEventListener('kimai.activityDelete', handle);
        document.addEventListener('kimai.projectUpdate', handle);
        document.addEventListener('kimai.projectDelete', handle);
        document.addEventListener('kimai.customerUpdate', handle);
        document.addEventListener('kimai.customerDelete', handle);
    }

    _toggleMenu(hasEntries) {
        this.menu.style.display = hasEntries ? 'inline-block' : 'none';
        if (!hasEntries) {
            // make sure that template entries in the menu are removed, otherwise they
            // might still be shown in the browsers title
            for (let record of this.menu.querySelectorAll('[data-since]')) {
                record.dataset['since'] = '';
            }
        }

        const menuEmpty = document.querySelector(this.selectorEmpty);
        if (menuEmpty !== null) {
            menuEmpty.style.display = !hasEntries ? 'inline-block' : 'none';
        }
    }

    setEntries(entries) {
        this._toggleMenu(entries.length > 0);

        const template = this.menu.querySelector('[data-template="active-record"]');

        const label = this.menu.querySelector('a > span.label');
        if (label !== null) {
            label.innerText = entries.length === 0 ? '' : entries.length;
        }

        if (entries.length === 0) {
            return;
        }

        if (template === null) {
            this._replaceInNode(this.menu, entries[0]);
        } else {
            const container = template.parentElement;
            container.innerHTML = '';

            for (let timesheet of entries) {
                const newNode = template.cloneNode(true);
                container.appendChild(this._replaceInNode(newNode, timesheet));
            }
        }

        this.getContainer().getPlugin('timesheet-duration').updateRecords();
    }

    _replaceInNode(node, timesheet) {
        const date = this.getContainer().getPlugin('date');
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
        const self = this;
        const API= this.getContainer().getPlugin('api');

        API.get(this.attributes['api'], {}, function(result) {
            self.setEntries(result);
        });
    }

}
