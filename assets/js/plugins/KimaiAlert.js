/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiAlert: notifications for Kimai
 */

import KimaiPlugin from "../KimaiPlugin";
import {Modal, Toast} from "bootstrap";

export default class KimaiAlert extends KimaiPlugin {

    /**
     * @return {string}
     */
    getId() {
        return 'alert';
    }

    /**
     * @param {string} title
     * @param {string|array|undefined} message
     */
    error(title, message) {
        const translation = this.getTranslation();
        if (translation.has(title)) {
            title = translation.get(title);
        }
        title = title.replace('%reason%', '');

        if (message === undefined) {
            message = null;
        }

        if (message !== null) {
            if (translation.has(message)) {
                message = translation.get(message);
            }
            if (Array.isArray(message)) {
                message = message.join('<br>');
            }
        }

        const id = 'alert_global_error';
        const oldModalElement = document.getElementById(id);
        if (oldModalElement !== null) {
            Modal.getOrCreateInstance(oldModalElement).hide();
        }

        const html = `
            <div class="modal modal-blur fade" id="` + id + `" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-status bg-` + this._mapClass('danger') + `"></div>
                        <div class="modal-body text-center py-4">
                            <i class="fas fa-exclamation-circle fa-3x mb-3 text-danger"></i>
                            <h2>` + title + `</h2>
                            ` + (message !== null ? '<div class="text-muted">' + message + '</div>' : '') + `
                        </div>
                        <div class="modal-footer">
                            <div class="w-100">
                                <div class="row">
                                    <div class="col text-center"><a href="#" class="btn btn-primary" data-bs-dismiss="modal">` + translation.get('close') + `</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this._showModal(html);
    }

    /**
     * @param {string} message
     */
    warning(message) {
        this._show('warning', message);
    }

    /**
     * @param {string} message
     */
    success(message) {
        this._toast('success', message);
    }

    /**
     * @param {string} message
     */
    info(message) {
        this._show('info', message);
    }

    /**
     * @param {string} html
     * @private
     */
    _showModal(html) {
        const container = document.body;
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const element = template.content.firstChild;
        container.appendChild(element);

        const modal = new Modal(element);
        element.addEventListener('hidden.bs.modal', function () {
            container.removeChild(element);
        });
        modal.show();
    }

    /**
     * @param {string} type
     * @param {string} message
     * @private
     */
    _show(type, message) {
        const translation = this.getTranslation();

        if (translation.has(message)) {
            message = translation.get(message);
        }

        const html = `
            <div class="modal modal-blur fade" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-status bg-` + this._mapClass(type) + `"></div>
                        <div class="modal-body text-center py-4">
                            <i class="fas fa-exclamation-circle fa-3x mb-3 text-` + this._mapClass(type) + `"></i>
                            <h2>` + message + `</h2>
                        </div>
                        <div class="modal-footer">
                            <div class="w-100">
                                <div class="row">
                                    <div class="col text-center"><a href="#" class="btn btn-primary" data-bs-dismiss="modal">` + translation.get('close') + `</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this._showModal(html);
    }

    /**
     * @param {string} type
     * @return {string}
     * @private
     */
    _mapClass(type) {
        if (type === 'info' || type === 'success' || type === 'warning' || type === 'danger') {
            return type;
        } else if (type === 'error') {
            return 'danger';
        }

        return 'primary';
    }

    /**
     * @param type
     * @param message
     * @private
     */
    _toast(type, message) {
        const translation = this.getTranslation();

        if (translation.has(message)) {
            message = translation.get(message);
        }

        let icon = '<i class="fas fa-info me-2"></i>';

        if (type === 'success') {
            icon = '<i class="fas fa-check me-2"></i>';
        } else if (type === 'warning') {
            icon = '<i class="fas fa-exclamation me-2"></i>';
        } else if (type === 'danger' || type === 'error') {
            icon = '<i class="fas fa-exclamation-circle me-2"></i>';
        }

        const html =
        `<div class="toast align-items-center text-white bg-` + this._mapClass(type) + ` border-0" data-bs-delay="2000" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ` + icon + ' ' + message + `
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="` + translation.get('close') + `"></button>
            </div>
        </div>`;

        const container = document.getElementById('toast-container');
        const template = document.createElement('template');

        template.innerHTML = html.trim();
        const element = template.content.firstChild;
        container.appendChild(element);

        const toast = new Toast(element);
        element.addEventListener('hidden.bs.toast', function () {
            container.removeChild(element);
        })
        toast.show();
    }

    /**
     * Callback receives a bool value (true = confirm, false = cancel / close without action).
     *
     * @param message
     * @param callback
     */
    question(message, callback) {
        const translation = this.getTranslation();

        if (translation.has(message)) {
            message = translation.get(message);
        }

        const css = this._mapClass('info');
        const html = `
            <div class="modal modal-blur fade" tabindex="-1" role="dialog" data-bs-backdrop="static">
                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-status bg-` + css + `"></div>
                        <div class="modal-body text-center py-4">
                            <i class="fas fa-question fa-3x mb-3 text-` + css + `"></i>
                            <h2>` + message + `</h2>
                        </div>
                        <div class="modal-footer">
                            <div class="w-100">
                                <div class="row">
                                    <div class="col"><a href="#" class="question-confirm btn btn-primary w-100" data-bs-dismiss="modal">` + translation.get('confirm') + `</a></div>
                                    <div class="col"><a href="#" class="question-cancel btn w-100" data-bs-dismiss="modal">` + translation.get('cancel') + `</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const container = document.body;
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const element = template.content.firstChild;
        container.appendChild(element);
        element.querySelector('.question-confirm').addEventListener('click', () => {
            callback(true);
        });
        element.querySelector('.question-cancel').addEventListener('click', () => {
            callback(false);
        });

        const modal = new Modal(element);
        element.addEventListener('hidden.bs.modal', () => {
            container.removeChild(element);
        });
        modal.show();
    }
}
