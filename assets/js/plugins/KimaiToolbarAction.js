/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import KimaiPlugin from '../KimaiPlugin';

/**
 * Needs to be initialized with a class name.
 *
 * A link like <a href=# class=remoteLink> can be activated with:
 * new KimaiToolbarAction('remoteLink')
 *
 * @param selector
 */
export default class KimaiToolbarAction extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    init() {
        const self = this;
        document.addEventListener('click', function(event) {
            let target = event.target;
            while (!target.matches('body')) {
                if (target.classList.contains(self.selector)) {
                    const form = document.querySelector('div.toolbar form.navbar-form');
                    if (form === null) {
                        return;
                    }
                    const prevAction = form.action;
                    const prevMethod = form.method;
                    form.target = '_blank';
                    form.action = target.href;
                    if (target.dataset.method !== undefined) {
                        form.method = target.dataset.method;
                    }
                    form.submit();
                    form.target = '';
                    form.action = prevAction;
                    form.method = prevMethod;

                    event.preventDefault();
                    event.stopPropagation();
                }

                target = target.parentNode;
            }
        });
    }

}
