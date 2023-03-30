/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiEditTimesheetForm: responsible for the most important form in the application
 */

import KimaiFormPlugin from "./KimaiFormPlugin";
import KimaiColor from "../widgets/KimaiColor";

export default class KimaiTeamForm extends KimaiFormPlugin {

    init()
    {
        this.usersId = 'team_edit_form_users';
    }

    /**
     * @param {HTMLFormElement} form
     * @return boolean
     */
    supportsForm(form)
    {
        return form.name === 'team_edit_form';
    }

    /**
     * @return {HTMLElement}
     * @private
     */
    _getPrototype()
    {
        return document.getElementById('team_edit_form_members');
    }

    /**
     * @param {HTMLFormElement} form
     */
    activateForm(form)
    {
        if (!this.supportsForm(form)) {
            return;
        }

        // must be attached to the form, because the button is added dynamically
        form.addEventListener('click', event => this._removeMember(event));

        document.getElementById(this.usersId).addEventListener('change', event => {
            const select = event.target;
            const option = select.options[select.selectedIndex];
            const member = this._createMember(option);
            this._getPrototype().append(member);
            this.getPlugin('form-select').removeOption(select, option);
        });
    }

    /**
     * @param {HTMLOptionElement} option
     * @returns {Element}
     * @private
     */
    _createMember(option)
    {
        /** @type {KimaiEscape} ESCAPER */
        const ESCAPER = this.getPlugin('escape');
        const prototype = this._getPrototype();
        let counter = prototype.dataset['widgetCounter'] || prototype.childNodes.length;
        let newWidget = prototype.dataset['prototype'];

        newWidget = newWidget.replace(/__name__/g, counter);

        newWidget = newWidget.replace(/#000000/g, KimaiColor.calculateContrastColor(option.dataset.color));
        newWidget = newWidget.replace(/__DISPLAY__/g, ESCAPER.escapeForHtml(option.dataset.display));
        newWidget = newWidget.replace(/__COLOR__/g, option.dataset.color);
        newWidget = newWidget.replace(/__INITIALS__/g, ESCAPER.escapeForHtml(option.dataset.initials));
        newWidget = newWidget.replace(/__TITLE__/g, ESCAPER.escapeForHtml(option.dataset.title));
        newWidget = newWidget.replace(/__USERNAME__/g, ESCAPER.escapeForHtml(option.text));

        prototype.dataset['widgetCounter'] = (++counter).toString();

        const temp = document.createElement('div');
        temp.innerHTML = newWidget;
        temp.querySelector('input[type=hidden]').value = option.value;

        const newNode = temp.firstElementChild;

        // copy over all initial settings, so we are able to rebuild the original option if the
        // member is removed from the list later on
        for (const key in option.dataset) {
            newNode.dataset[key] = option.dataset[key];
        }

        return newNode;
    }

    /**
     * @param {Event} event
     * @private
     */
    _removeMember(event)
    {
        let button = event.target;

        if (button.parentNode.matches('.remove-member')) {
            button = button.parentNode;
        }

        if (button.matches('.remove-member')) {
            // see blocks.html.twig => block team_member_widget
            const element = button.parentNode.parentNode.parentNode.parentNode.parentNode;

            // re-adding the option to the select makes up for form validation errors
            // because the list would have to be re-ordered and indices need to be changed ...
            /*
            this.getPlugin('form-select').addOption(
                document.getElementById(this.usersId),
                element.dataset['display'],
                element.dataset['id'],
                element.dataset
            );
            const prototype = this._getPrototype();
            prototype.dataset['widgetCounter'] = (prototype.dataset['widgetCounter'] - 1).toString();
            */

            element.remove();
            event.stopPropagation();
            event.preventDefault();
        }
    }

    /**
     * @param {HTMLFormElement} form
     */
    destroyForm(form)
    {
        if (!this.supportsForm(form)) {
            return;
        }

        form.removeEventListener('click', this._removeMember);
    }

}
