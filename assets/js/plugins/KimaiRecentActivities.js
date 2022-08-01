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

    getId()
    {
        return 'recent-activities';
    }

    init()
    {
        this.menu = document.querySelector('header .notifications-menu');
        // the menu can be hidden if user has no permissions to see it
        // or no timesheet was recorded yet
        if (this.menu === null || this.menu.dataset['reload'] === undefined) {
            return;
        }

        const handle = () => {
            this.fetch(this.menu.dataset['reload'], {method: 'GET'})
                .then(response => {
                    if (!response.ok) {
                        this.menu.remove();
                        return;
                    }

                    return response.text().then(html => {
                        const newFormHtml = document.createElement('div');
                        newFormHtml.innerHTML = html;
                        this.menu.replaceWith(newFormHtml.firstElementChild);
                    });
                })
                .catch(() =>  {
                    this.menu.remove();
                });
        };

        document.addEventListener('kimai.recentActivities', handle);
        document.addEventListener('kimai.timesheetUpdate', handle);
        document.addEventListener('kimai.timesheetDelete', handle);
        document.addEventListener('kimai.activityUpdate', handle);
        document.addEventListener('kimai.activityDelete', handle);
        document.addEventListener('kimai.projectUpdate', handle);
        document.addEventListener('kimai.projectDelete', handle);
        document.addEventListener('kimai.customerUpdate', handle);
        document.addEventListener('kimai.customerDelete', handle);
    }

}
