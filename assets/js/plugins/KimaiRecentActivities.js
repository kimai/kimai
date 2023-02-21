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

    constructor()
    {
        super();
        this._selector = '.notifications-menu';
    }

    /**
     * @returns {string}
     */
    getId()
    {
        return 'recent-activities';
    }

    init()
    {
        const menus = document.querySelectorAll(this._selector);

        // the menu can be hidden if user has no permissions to see it
        // or no timesheet was recorded yet
        if (menus.length === 0 || menus[0].dataset['reload'] === undefined) {
            return;
        }

        const handle = () => {
            // TODO this works but using the first menu is not ideal, pass in the reload URL?
            this._reloadMenu(menus[0].dataset['reload']);
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

        this._attachAddRemoveFavorite();
    }

    /**
     * @private
     */
    _attachAddRemoveFavorite()
    {
        [].slice.call(document.querySelectorAll(this._selector + ' a.list-group-item-actions')).map((element) => {
            element.addEventListener('click', (event) => {
                this._reloadMenu(event.currentTarget.href);

                event.preventDefault();
                event.stopPropagation();

                return false;
            });
        });
    }

    /**
     * Reload all ercent activities and update the existing menus.
     *
     * @param {string} url
     * @private
     */
    _reloadMenu(url)
    {
        this.fetch(url, {method: 'GET'})
            .then(response => {
                if (!response.ok) {
                    return;
                }

                return response.text().then(html => {
                    const newFormHtml = document.createElement('div');
                    newFormHtml.innerHTML = html;

                    for (let menu of document.querySelectorAll(this._selector)) {
                        menu.replaceWith(newFormHtml.firstElementChild.cloneNode(true));
                    }

                    this._attachAddRemoveFavorite();
                });
            })
            .catch((reason) =>  {
                console.log('Failed to log recent activities', reason);
            });
    }
}
