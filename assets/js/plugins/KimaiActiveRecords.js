/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiRecentActivities: responsible to reload the users recent activities
 */

import KimaiPlugin from '../KimaiPlugin';

export default class KimaiActiveRecords extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'recent-activities';
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

        this.label.innerText = '99';

        const self = this;
        const handle = function() { self.reload(); };

        // don't block initial browser rendering
        setTimeout(handle, 500);

        // TODO other events
        document.addEventListener('kimai.activityUpdate', handle);
        document.addEventListener('kimai.projectUpdate', handle);
        document.addEventListener('kimai.customerUpdate', handle);
    }

    emptyList() {
        this.itemList.innerHTML = '';
    }

    setEntries(entries) {
        if (entries.length === 0) {
            this.emptyList();
            return;
        }

        let htmlToInsert = '';

        for (let timesheet of entries) {
            let label = this.attributes['template']
                .replace('%customer%', timesheet.project.customer.name)
                .replace('%project%', timesheet.project.name)
                .replace('%activity%', timesheet.activity.name);

            htmlToInsert += `<li><a href="${ this.attributes['url'].replace('000', timesheet.id) }"><i class="${ this.attributes['icon'] }"></i> ${ label }</a></li>`;
        }

        this.itemList.innerHTML = htmlToInsert;
    }

    reload() {
        const self = this;
        const apiService = this.getContainer().getPlugin('api');

        apiService.get(this.attributes['api'], function(result) {
            self.setEntries(result);
        });
    }

}
