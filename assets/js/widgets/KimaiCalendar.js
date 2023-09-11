/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiCalendar: wrapping Fullcalendar.io
 */
import { Popover } from 'bootstrap';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import bootstrap5Plugin, { BootstrapTheme } from '@fullcalendar/bootstrap5';
import googlePlugin from '@fullcalendar/google-calendar';
import iCalendarPlugin from '@fullcalendar/icalendar'
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';
import arLocale from '@fullcalendar/core/locales/ar';
import csLocale from '@fullcalendar/core/locales/cs';
import daLocale from '@fullcalendar/core/locales/da';
import deLocale from '@fullcalendar/core/locales/de';
import deAtLocale from '@fullcalendar/core/locales/de-at';
import elLocale from '@fullcalendar/core/locales/el';
import esLocale from '@fullcalendar/core/locales/es';
import euLocale from '@fullcalendar/core/locales/eu';
import faLocale from '@fullcalendar/core/locales/fa';
import fiLocale from '@fullcalendar/core/locales/fi';
import frLocale from '@fullcalendar/core/locales/fr';
import heLocale from '@fullcalendar/core/locales/he';
import hrLocale from '@fullcalendar/core/locales/hr';
import huLocale from '@fullcalendar/core/locales/hu';
import itLocale from '@fullcalendar/core/locales/it';
import jaLocale from '@fullcalendar/core/locales/ja';
import koLocale from '@fullcalendar/core/locales/ko';
import nbLocale from '@fullcalendar/core/locales/nb';
import nlLocale from '@fullcalendar/core/locales/nl';
import plLocale from '@fullcalendar/core/locales/pl';
import ptLocale from '@fullcalendar/core/locales/pt';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import roLocale from '@fullcalendar/core/locales/ro';
import ruLocale from '@fullcalendar/core/locales/ru';
import skLocale from '@fullcalendar/core/locales/sk';
import svLocale from '@fullcalendar/core/locales/sv';
import trLocale from '@fullcalendar/core/locales/tr';
import zhLocale from '@fullcalendar/core/locales/zh-cn';
import viLocale from '@fullcalendar/core/locales/vi';
import enGbLocale from '@fullcalendar/core/locales/en-gb';
import enUsLocale from '@fullcalendar/core/locales/en-gb';
import KimaiColor from './KimaiColor';
import KimaiContextMenu from "./KimaiContextMenu";

export default class KimaiCalendar {

    /**
     * Options is a huge JSON object.
     *
     * @param {KimaiContainer} kimai
     * @param {HTMLElement} element
     * @param {Object} options
     */
    constructor(kimai, element, options) {
        this.kimai = kimai;
        this.options = options;

        /** @type {KimaiAPI} API */
        const API = this.kimai.getPlugin('api');
        /** @type {KimaiDateUtils} DATES */
        const DATES = this.kimai.getPlugin('date');
        /** @type {KimaiAjaxModalForm} MODAL */
        const MODAL = this.kimai.getPlugin('modal');
        /** @type {KimaiAlert} ALERT */
        const ALERT = this.kimai.getPlugin('alert');

        let initialView = 'dayGridMonth';
        switch (options['initialView']) {
            case 'month':
                initialView = 'dayGridMonth';
                break;
            case 'agendaWeek':
            case 'week':
                initialView = 'timeGridWeek';
                break;
            case 'agendaDay':
            case 'day':
                initialView = 'timeGridDay';
                break;
        }

        // Instead of using "buttonIcons" the theme needs to be adjusted directly
        // https://fullcalendar.io/docs/buttonIcons
        BootstrapTheme.prototype.classes = {
            root: 'fc-theme-bootstrap5',
            tableCellShaded: 'fc-theme-bootstrap5-shaded',
            buttonGroup: 'btn-group',
            button: 'btn btn-primary btn-icon', // required for Tabler
            buttonActive: 'active',
            popover: 'popover',
            popoverHeader: 'popover-header',
            popoverContent: 'popover-body',
        };
        BootstrapTheme.prototype.baseIconClass = '';  // required for Fontawesome
        BootstrapTheme.prototype.iconOverridePrefix = '';  // required for Fontawesome
        BootstrapTheme.prototype.iconClasses = {
            close: 'fa-times',
            prev: this.options['icons']['previous'],
            next: this.options['icons']['next'],
            prevYear: this.options['icons']['previousYear'],
            nextYear: this.options['icons']['nextYear'],
        };
        BootstrapTheme.prototype.rtlIconClasses = {
            prev: this.options['icons']['next'],
            next: this.options['icons']['previous'],
            prevYear: this.options['icons']['nextYear'],
            nextYear: this.options['icons']['previousYear'],
        };

        let calendarOptions = {
            locales: [ enGbLocale, enUsLocale, arLocale, csLocale, daLocale, deLocale, deAtLocale, elLocale,
                esLocale, euLocale, faLocale, fiLocale, frLocale, heLocale, hrLocale, huLocale, itLocale, jaLocale, koLocale,
                nbLocale, nlLocale, plLocale, ptLocale, ptBrLocale, roLocale, ruLocale, skLocale, svLocale, trLocale, zhLocale, viLocale ],
            plugins: [ bootstrap5Plugin, dayGridPlugin, timeGridPlugin, googlePlugin, iCalendarPlugin, interactionPlugin ],
            initialView: initialView,
            // https://fullcalendar.io/docs/theming
            themeSystem: 'bootstrap5',
            // https://fullcalendar.io/docs/headerToolbar
            headerToolbar: {
                start: 'title',
                center: 'dayGridMonth,timeGridWeek,timeGridDay',
                end: 'today prev,next'
            },
            direction: this.kimai.getConfiguration().get('direction'),
            locale: this.kimai.getConfiguration().getLanguage().toLowerCase(),

            // https://fullcalendar.io/docs/height
            // auto makes the calendar too small
            // height: 'auto',
            height: '80vh',

            // allow clicking e.g. week-numbers to change the view to this week
            navLinks: true,
            nowIndicator: true,
            weekends: this.options['showWeekends'],
            weekNumbers: this.options['showWeekNumbers'],
            weekNumberCalculation: 'ISO',
            firstDay: this.kimai.getConfiguration().getFirstDayOfWeek(true),

            now: this.options['now'],
            businessHours: {
                daysOfWeek: [0, 1, 2, 3, 4, 5, 6],
                startTime: this.options['businessTimeBegin'],
                endTime: this.options['businessTimeEnd']
            },
            slotDuration: this.options['slotDuration'],
            slotMinTime: this.options['timeframeBegin'] + ':00',
            slotMaxTime: this.options['timeframeEnd'] === '23:59' ? '24:00:00' : (this.options['timeframeEnd'] + ':59'),

            // auto calculation seems to do the better job, therefor deactivated
            //slotLabelInterval: this.options['slotDuration'],

            // how long should entries look like when they don't have an end
            defaultTimedEventDuration: this.options['slotDuration'],

            // https://fullcalendar.io/docs/timeZone
            timeZone: this.options['timezone'],

            // TODO implement me later on
            // https://fullcalendar.io/docs/validRange
            // limit to the users registration date or a configuration for the first day in job

            // https://fullcalendar.io/docs/hiddenDays
            // once we can configure working days
            // hiddenDays: [ 2, 4 ]

            // when we support holidays and other full day events
            // allDaySlot: false,
            // dropAccept

            dayMaxEventRows: true,
            eventMaxStack: this.options['dayLimit'],
            dayMaxEvents: this.options['dayLimit'],

            views: {
                dayGrid: {
                    dayMaxEventRows: this.options['dayLimit']
                }
            },

            // ============= POPOVER =============
            viewClassNames: () => {
                document.querySelector('.fc-dayGridMonth-button').classList.remove('btn-icon');
                document.querySelector('.fc-timeGridWeek-button').classList.remove('btn-icon');
                document.querySelector('.fc-timeGridDay-button').classList.remove('btn-icon');
            },

            // DESTROY TO PREVENT MEMORY LEAKS
            eventWillUnmount: (unmountInfo) => {
                // this happens when a user drags an external event to the calendar (view: week and day) and moves it around
                // for some reason the "eventWillUnmount" is triggered for this "potential but not yet existing event"
                if (unmountInfo.event.source === null) {
                    return;
                }

                if (!this.isKimaiSource(unmountInfo.event)) {
                    return;
                }
                const popover = Popover.getInstance(unmountInfo.element);
                if (popover !== null) {
                    popover.dispose();
                }
            },

            // SHOW POPOVER FOR TIMESHEETS
            eventMouseEnter: (mouseEnterInfo) => {
                const event = mouseEnterInfo.event;

                if (!this.isKimaiSource(event)) {
                    // TODO allow to copy into kimai
                    return;
                }

                const element = mouseEnterInfo.el;
                const popoverTitle = DATES.getFormattedDate(event.start) + ' | ' + DATES.formatTime(event.start) + ' - ' + (event.end ? DATES.formatTime(event.end) : '');
                const popoverContent = this.renderEventPopoverContent(event);

                let popover = Popover.getInstance(element);
                if (popover !== null) {
                    // see https://github.com/kimai/kimai/issues/4043
                    popover.setContent({
                        '.popover-header': popoverTitle,
                        '.popover-body': popoverContent
                    });
                } else {
                    // https://getbootstrap.com/docs/5.0/components/popovers/#options
                    popover = new Popover(element, {
                        title: popoverTitle,
                        placement: 'top',
                        html: true,
                        content: popoverContent,
                        trigger: 'focus',
                    });
                }

                popover.show();
            },

            // HIDE POPOVER
            eventMouseLeave: (mouseLeaveInfo) => {
                if (!this.isKimaiSource(mouseLeaveInfo.event)) {
                    return;
                }

                this.hidePopover(mouseLeaveInfo.el);
            },

            // ContextMenu
            eventDidMount: (arg) => {
                arg.el.addEventListener('contextmenu', (jsEvent) => {
                    jsEvent.preventDefault();
                    const event = arg.event;
                    if (!event.allDay) {
                        const url = this.options.url.actions(event.extendedProps.timesheet);
                        API.get(url, {}, result => {
                            const contextMenu = new KimaiContextMenu('calendar_contextMenu');
                            contextMenu.createFromApi(jsEvent, result);
                        }, (e) => { console.log('Failed to load actions for context menu', e); });
                    }
                })
            },
        };

        // ============= DRAG & DROP =============

        if (!this.hasPermission('punch') && this.hasPermission('create') && this.options.dragdrop !== undefined) {
            const draggableList = [].slice.call(document.querySelectorAll(this.options.dragdrop.container));
            draggableList.map((containerEl) => {
                return new Draggable(containerEl, {
                    itemSelector: this.options.dragdrop.items
                });
            });

            calendarOptions = {...calendarOptions, ...{
                droppable: true,
                // drop function handles external draggable events
                drop: (dropInfo) => {
                    const entry = dropInfo.draggedEl;
                    const source = entry.parentElement;
                    let data = JSON.parse(entry.dataset.entry);

                    const urlReplacer = JSON.parse(source.dataset.routeReplacer);
                    let apiUrl = source.dataset.route;

                    for (const [key, value] of Object.entries(urlReplacer)) {
                        apiUrl = apiUrl.replace(key, data[value]);
                    }

                    let begin = dropInfo.date;

                    if (dropInfo.view.type === 'dayGridMonth') {
                        let defaultStartTime = this.options.defaultStartTime;
                        if (defaultStartTime === null) {
                            const now = new Date();
                            defaultStartTime = (now.getHours() < 10 ? '0' : '') + now.getHours() + ':' + (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
                        }
                        begin = DATES.addHumanDuration(begin, defaultStartTime);
                    }

                    let end = DATES.addHumanDuration(begin, this.options['slotDuration']);

                    if (!this.hasPermission('punch')) {
                        if (this.hasPermission('edit_begin')) {
                            data.begin = DATES.formatForAPI(begin);
                        }
                        if (this.hasPermission('edit_end')) {
                            data.end = DATES.formatForAPI(end);
                        }
                    }

                    data = this.options.preparePayloadForUpdate(data);

                    if (source.dataset.method === 'PATCH') {
                        API.patch(
                            apiUrl,
                            JSON.stringify(data),
                            (result) => {
                                const newItem = this.convertSourceForCalendar(result);
                                this.getCalendar().addEvent(newItem, true);
                                ALERT.success('action.update.success');
                            }
                        );
                    } else {
                        API.post(
                            apiUrl,
                            JSON.stringify(data),
                            (result) => {
                                const newItem = this.convertSourceForCalendar(result);
                                this.getCalendar().addEvent(newItem, true);
                                ALERT.success('action.update.success');
                            }
                        );
                    }
                },
            }};
        }

        // ============= CREATE NEW RECORDS =============

        // After click or selection, not allowed for everyone
        if (!this.hasPermission('punch') && this.hasPermission('create')) {
            calendarOptions = {...calendarOptions, ...{
                dateClick: (dateClickInfo) => {
                    // Day-clicks are always triggered, unless a selection was created.
                    // So clicking in a day (month view) or any slot (week and day view) will trigger a dayClick
                    // BEFORE triggering a select - make sure not two create dialogs are requested
                    if (dateClickInfo.view.type !== 'dayGridMonth') {
                        return;
                    }

                    const createUrl = this.options.url.create(dateClickInfo.dateStr);
                    MODAL.openUrlInModal(createUrl);
                },
                selectable: true,
                select: (selectionInfo) => {
                    if(selectionInfo.view.type === 'dayGridMonth') {
                        // Multi-day clicks are NOT allowed in the month view, as simple day clicks would also trigger
                        // a select - there is no way to distinguish a simple click and a two-day selection
                        return;
                    }

                    const createUrl = this.options.url.create(selectionInfo.startStr, selectionInfo.endStr);
                    MODAL.openUrlInModal(createUrl);
                },
            }};
        }

        // ============= EDIT TIMESHEET =============

        if (this.hasPermission('edit')) {
            calendarOptions = {...calendarOptions, ...{
                eventClick: (eventClickInfo) => {
                    const event = eventClickInfo.event;
                    if (!this.isKimaiSource(event)) {
                        eventClickInfo.jsEvent.preventDefault();
                        return;
                    }
                    this.hidePopover(eventClickInfo.el);

                    if (!event.extendedProps.exported || this.hasPermission('edit_exported')) {
                        MODAL.openUrlInModal(
                            this.options.url.edit(event.id), (reason) => {
                                // 403 = user is not allowed to edit the entry (e.g. lockdown mode)
                                if (reason.status !== 403) {
                                    // keep the log, it might help with debugging
                                    console.log(reason);
                                }
                            }
                        );
                    }
                },
            }};

            // UPDATE TIMESHEET - MOVE THEM OR EXTEND THEM
            if (!this.hasPermission('punch')) {
                calendarOptions = {...calendarOptions, ...{
                    // https://fullcalendar.io/docs/event-dragging-resizing
                    dragRevertDuration: 0,
                    eventStartEditable: this.hasPermission('edit_begin'),
                    eventDurationEditable: this.hasPermission('edit_end') || this.hasPermission('edit_duration'),
                    eventDragStart: (info) => {
                        this.hidePopover(info.el);
                    },
                    eventDrop: (eventDropInfo) => {
                        this.changeHandler(eventDropInfo)
                    },
                    eventResizeStart: (info) => {
                        this.hidePopover(info.el);
                    },
                    eventResize: (eventResizeInfo) => {
                        this.changeHandler(eventResizeInfo)
                    },
                }};
            }
        }

        // ============= GOOGLE CALENDAR =============

        if (this.options['googleCalendarApiKey'] !== undefined) {
            calendarOptions = {...calendarOptions, ...{
                // https://fullcalendar.io/docs/google-calendar
                googleCalendarApiKey: this.options['googleCalendarApiKey'],
            }};
        }

        // ============= EVENT SOURCES =============

        let eventSources = [];
        for (const source of this.options['eventSources']) {
            let calendarSource = {};
            if (source.type === 'timesheet') {
                calendarSource = {...calendarSource, ...{
                    id: 'kimai-' + source.id,
                    events: (fetchInfo, successCallback, failureCallback) => {
                        const targetFrom = DATES.formatForAPI(fetchInfo.start);
                        const targetTo = DATES.formatForAPI(fetchInfo.end);

                        let url = source.url;
                        url = url.replace('{from}', targetFrom);
                        url = url.replace('__FROM__', targetFrom);
                        url = url.replace('{to}', targetTo);
                        url = url.replace('__TO__', targetTo);

                        API.get(url, {}, result => {
                            let apiEvents = [];
                            for (const record of result) {
                                apiEvents.push(this.convertSourceForCalendar(record));
                            }
                            successCallback(apiEvents);
                        }, failureCallback);
                    },
                }};
            } else if (source.type === 'google') {
                calendarSource = {...calendarSource, ...{
                    id: 'google-' + source.id,
                    name: 'google',
                    editable: false,
                }};
            } else if (source.type === 'json') {
                calendarSource = {...calendarSource, ...{
                    id: 'json-' + source.id,
                    editable: false,
                    events: (fetchInfo, successCallback, failureCallback) => {
                        const targetFrom = DATES.formatForAPI(fetchInfo.start);
                        const targetTo = DATES.formatForAPI(fetchInfo.end);

                        let url = source.url;
                        url = url.replace('{from}', targetFrom);
                        url = url.replace('__FROM__', targetFrom);
                        url = url.replace('{to}', targetTo);
                        url = url.replace('__TO__', targetTo);

                        API.get(url, {}, result => {
                            let apiEvents = [];
                            for (const record of result) {
                                apiEvents.push(record);
                            }
                            successCallback(apiEvents);
                        }, failureCallback);
                    },
                }};
            } else if (source.type === 'ical') {
                calendarSource = {...calendarSource, ...{
                    id: 'ical-' + source.id,
                    url: source.url,
                    format: 'ics',
                    editable: false,
                }};
            } else {
                console.log('Unknown source type given, skipping to load events from: ' + source.id);
                continue;
            }
            if (source.options !== undefined) {
                calendarSource = {...calendarSource, ...source.options};
            }
            eventSources.push(calendarSource);
        }

        if (eventSources.length > 0) {
            calendarOptions = {...calendarOptions, ...{
                eventSources: eventSources,
            }};
        }

        // INITIALIZE CALENDAR
        this.calendar = new Calendar(element, calendarOptions);
    }

    /**
     * @param {EventApi} event
     * @return {boolean}
     * @private
     */
    isKimaiSource(event) {
        if (event === null) {
            return false;
        }
        if (event.source === null) {
            return false;
        }
        return (event.source.id.indexOf('kimai-') === 0);
    }

    /**
     * @param {string} name
     * @return {boolean}
     * @private
     */
    hasPermission(name) {
        return this.options['permissions'][name];
    }

    /**
     * @return {Calendar}
     */
    getCalendar() {
        return this.calendar;
    }

    render() {
        this.calendar.render();
    }

    reloadEvents() {
        this.calendar.getEventSources().forEach(source => source.refetch());
    }

    /**
     * Only used on manipulated timesheets!
     *
     * @param {object} apiItem
     * @return {{activity, color: *, start, description, project, end, id, title: *, textColor: *, customer, tags: ([number,number,[],string,string]|*)}}
     * @private
     */
    convertSourceForCalendar(apiItem) {
        const defaultColor = this.kimai.getConfiguration().get('defaultColor');
        let color = apiItem.activity.color;
        if (color === null || color === defaultColor) {
            color = apiItem.project.color;
            if (color === null || color === defaultColor) {
                color = apiItem.project.customer.color;
            }
        }
        if (color == null) {
            color = defaultColor;
        }

        /** @type {KimaiDateUtils} DATES */
        const DATES = this.kimai.getPlugin('date');

        let title = this.options['patterns']['title'];
        title = title.replace('{project}', apiItem.project.name);
        title = title.replace('{customer}', apiItem.project.customer.name);
        title = title.replace('{description}', apiItem.description ?? '');
        title = title.replace('{activity}', apiItem.activity.name ?? '');

        if (apiItem.end === null) {
            // duration = 0 and end = null => is a running entry
            title = title.replace('{duration}', '');
        } else {
            title = title.replace('{duration}', DATES.formatDuration(apiItem.duration));
        }

        if (title === '' || title === null) {
            title = apiItem.activity.name;
        }

        return {
            id: apiItem.id,
            timesheet: apiItem.id,
            title: title,
            description: apiItem.description,
            exported: apiItem.exported,
            start: apiItem.begin,
            end: apiItem.end,
            activity: apiItem.activity.name,
            project: apiItem.project.name,
            customer: apiItem.project.customer.name,
            tags: apiItem.tags,
            color: color,
            textColor: KimaiColor.calculateContrastColor(color),
        };
    }

    /**
     * @param {EventApi} event
     * @return {string}
     * @private
     */
    renderEventPopoverContent(event) {
        const eventObj = event.extendedProps;
        /** @type {KimaiEscape} escaper */
        const escaper = this.kimai.getPlugin('escape');

        let tags = '';
        if (eventObj.tags !== null && eventObj.tags.length > 0) {
            for (let tag of eventObj.tags) {
                tags += '<span class="badge bg-green">' + escaper.escapeForHtml(tag) + '</span>';
            }
        }

        return `
            <div class="calendar-entry">
                <ul>
                    <li>` + this.options['translations']['customer'] + `: ` + escaper.escapeForHtml(eventObj.customer) + `</li>
                    <li>` + this.options['translations']['project'] + `: ` + escaper.escapeForHtml(eventObj.project) + `</li>
                    <li>` + this.options['translations']['activity'] + `: ` + escaper.escapeForHtml(eventObj.activity) + `</li>
                </ul>` +
                (eventObj.description !== null || eventObj.tags.length > 0 ? '<hr>' : '') +
                (eventObj.description ? '<div>' + escaper.escapeForHtml(eventObj.description) + '</div>' : '') + tags + `
            </div>`;
    }

    /**
     * @param {HTMLElement} element
     * @private
     */
    hidePopover(element) {
        let popover = Popover.getInstance(element);

        if (popover !== null) {
            popover.hide();
        }
    }

    /**
     * @param {EventDropArg} eventArg
     * @private
     */
    changeHandler(eventArg) {
        /** @type {EventApi} event */
        const event = eventArg.event;

        if (event.extendedProps.exported && !this.hasPermission('edit_exported')) {
            eventArg.revert();
            return;
        }

        /** @type {KimaiAPI} API */
        const API = this.kimai.getPlugin('api');
        /** @type {KimaiAlert} ALERT */
        const ALERT = this.kimai.getPlugin('alert');
        /** @type {KimaiDateUtils} DATE */
        const DATES = this.kimai.getPlugin('date');

        let payload = {'begin': DATES.formatForAPI(event.start)};

        if (event.end !== null && event.end !== undefined) {
            payload.end = DATES.formatForAPI(event.end);
        } else {
            payload.end = null;
        }

        const updateUrl = this.options.url.update(event.id);
        API.patch(updateUrl, JSON.stringify(payload), () => {
            ALERT.success('action.update.success');
        }, (error) => {
            eventArg.revert();
            API.handleError('action.update.error', error);
        });
    }

}
