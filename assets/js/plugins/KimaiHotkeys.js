/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiHotkeys extends KimaiPlugin {

    getId()
    {
        return 'hotkeys';
    }

    init()
    {
        // https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent
        // https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/key

        const selector = '[data-hotkey="ctrl+Enter"]';

        window.addEventListener('keyup', (ev) => {
            if (ev.ctrlKey && ev.key === 'Enter') {
                const elements = [...document.querySelectorAll(selector)].filter(element => this.isVisible(element));

                if (elements.length > 1) {
                    console.warn('KimaiHotkeys: More than one visible element matches ${selector}. No action triggered.');
                }

                if (elements.length === 1) {
                    ev.stopPropagation();
                    ev.preventDefault();

                    elements[0].click();
                }
            }
        });
    }

    // adopted from Bootstrap 5.1.1, MIT
    isVisible (element)
    {
        if (!element || element.getClientRects().length === 0) {
            return false;
        }

        return getComputedStyle(element).getPropertyValue('visibility') === 'visible';
    }
}