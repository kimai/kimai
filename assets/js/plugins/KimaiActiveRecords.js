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
        const menu = document.querySelector(this.selector);

        // the menu can be hidden if user has no permissions to see it
        if (menu === null) {
            return;
        }

        const dropdown = menu.querySelector('ul.dropdown-menu');

        this.attributes = dropdown.dataset;
        this.itemList = dropdown.querySelector('li > ul.menu');
        this.label = menu.querySelector('a > span.label');

        const self = this;
        const handle = function() { self.reloadActiveRecords(); };

        document.addEventListener('kimai.timesheetUpdate', handle);
        document.addEventListener('kimai.activityUpdate', handle);
        document.addEventListener('kimai.projectUpdate', handle);
        document.addEventListener('kimai.customerUpdate', handle);
    }

    emptyList() {
        this.itemList.innerHTML = '';
    }

    _toggleMenu(hasEntries) {
        const menu = document.querySelector(this.selector);
        const menuEmpty = document.querySelector(this.selectorEmpty);

        menu.style.display = hasEntries ? 'inline-block' : 'none';
        if (menuEmpty !== null) {
            menuEmpty.style.display = !hasEntries ? 'inline-block' : 'none';
        }
    }

    setEntries(entries) {
        this._toggleMenu(entries.length > 0);

        if (entries.length === 0) {
            this.label.innerText = '';
            this.emptyList();
            return;
        }

        let htmlToInsert = '';
        const durations = this.getContainer().getPlugin('timesheet-duration');

        for (let timesheet of entries) {
            htmlToInsert +=
                    `<li>` +
                        `<a href="${ this.attributes['href'].replace('000', timesheet.id) }" data-event="kimai.timesheetStop kimai.timesheetUpdate" class="api-link" data-method="PATCH" data-msg-error="timesheet.stop.error" data-msg-success="timesheet.stop.success">` +
                            `<div class="pull-left">` +
                                `<i class="${ this.attributes['icon'] } fa-2x"></i>` +
                            `</div>` +
                            `<h4>` +
                                `<span>${ timesheet.activity.name }</span>` +
                                `<small>` +
                                    `<span data-title="true" data-since="${ timesheet.begin }" data-format="${ this.attributes['format'] }">${ durations.formatDuration(timesheet.duration, this.attributes['format']) }</span>` +
                                `</small>` +
                            `</h4>` +
                            `<p>${ timesheet.project.name } (${ timesheet.project.customer.name })</p>` +
                        `</a>` +
                    `</li>`;
        }

        if (this.label.dataset.warning < entries.length) {
            this.label.classList = 'label label-danger';
        } else {
            this.label.classList = 'label label-warning';
        }
        this.label.innerText = entries.length;
        this.itemList.innerHTML = htmlToInsert;

        durations.updateRecords();
    }

    reloadActiveRecords() {
        const self = this;
        const apiService = this.getContainer().getPlugin('api');

        apiService.get(this.attributes['api'], {}, function(result) {
            self.setEntries(result);
        });
    }

}
