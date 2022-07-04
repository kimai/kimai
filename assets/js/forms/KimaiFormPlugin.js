/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiFormPlugin: base class for all none ID plugin that handle forms
 */

import KimaiPlugin from '../KimaiPlugin';

export default class KimaiFormPlugin extends KimaiPlugin {

    /**
     * @param {HTMLFormElement} form
     * @return boolean
     */
    supportsForm(form) // eslint-disable-line no-unused-vars
    {
        return false;
    }

    /**
     * @param {HTMLFormElement} form
     */
    activateForm(form) // eslint-disable-line no-unused-vars
    {
    }

    /**
     * @param {HTMLFormElement} form
     */
    destroyForm(form) // eslint-disable-line no-unused-vars
    {
    }

}
