/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiUser: information about the current user
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiUser extends KimaiPlugin {

    getId() {
        return 'user';
    }

    init() {
        this.user = this.getConfigurations().get('user');
    }

    /**
     * @returns {string}
     */
    getUserId() {
        return this.user.id;
    }

    /**
     * @returns {string}
     */
    getName() {
        return this.user.name;
    }

    /**
     * @returns {boolean}
     */
    isAdmin() {
        return this.user.admin;
    }

    /**
     * @returns {boolean}
     */
    isSuperAdmin() {
        return this.user.superAdmin;
    }

}
