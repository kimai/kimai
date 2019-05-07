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

export default class KimaiRecentActivities extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'recent-activities';
    }

    init() {
        let recentMenu = document.querySelector(this.selector);
        if (recentMenu === null) {
            return;
        }

        this.menu = recentMenu.querySelector('ul.dropdown-menu');
        this.label = recentMenu.querySelector('a.dropdown-toggle span.label');

        // don't block rendering
        let self = this;
        setTimeout(
            function() {
                self.reload();
            },
            500
        );

        this._activateListener();
    }

    _activateListener() {
        const self = this;
        const handle = function() { self.reload(); };
        document.addEventListener('kimai.activityUpdate', handle);
        document.addEventListener('kimai.projectUpdate', handle);
        document.addEventListener('kimai.customerUpdate', handle);
    }

    _getApiUrl() {
        return this.menu.getAttribute('data-api');
    }

    _getLabel(timesheet) {
        return this.menu.getAttribute('data-template')
            .replace('%customer%', timesheet.project.customer.name)
            .replace('%project%', timesheet.project.name)
            .replace('%activity%', timesheet.activity.name)
        ;
    }

    _getTemplate(timesheet) {
        return `<li><a href="${ this._getStartUrl(timesheet) }"><i class="${ this._getIcon() }"></i> ${ this._getLabel(timesheet) }</a></li>`;
    }

    _getIcon() {
        return this.menu.getAttribute('data-icon');
    }

    _getStartUrl(timesheet) {
        return this.menu.getAttribute('data-url').replace('000', timesheet.id);
    }

    _getMenu() {
        return this.menu.querySelector('li > ul.menu');
    }

    emptyList() {
        this._getMenu().innerHTML = '';
    }

    setEntries(entries) {
        let mainMenu = document.querySelector(this.selector);

        if (entries.length === 0) {
            mainMenu.style.display = 'none';
            this.emptyList();
            return;
        }

        let htmlToInsert = '';

        for (let timesheet of entries) {
            htmlToInsert += this._getTemplate(timesheet);
        }

        this._getMenu().innerHTML = htmlToInsert;

        mainMenu.style.display = 'block';
    }

    reload() {
        const self = this;
        const apiService = this.getContainer().getPlugin('api');

        apiService.get(this._getApiUrl(), function(result) {
            self.setEntries(result);
        });
    }

}
