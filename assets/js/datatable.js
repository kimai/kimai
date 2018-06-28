/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

global.Cookies = require('js-cookie');

if (typeof jQuery === 'undefined') {
    throw new Error('Kimai requires jQuery');
}

/* datatable
 *
 * @type Object
 * @description $.datatable is the main object for views with data-tables.
 *              It's used for implementing functions and options related
 *              to the datatables.
 */
$.datatable = {};

$(function() {
"use strict";

    $.datatable = {
        saveVisibility: function (modalSelector) {
            var settings = {};
            var cookieName = $(modalSelector).find('form').attr('name');
            var columns = $(modalSelector).find('form').find('input:checkbox:not(:checked)').each(
                function (i) {
                    settings[$(this).attr('name')] = $(this).is(':checked');
                }
            );
            if (jQuery.isEmptyObject(settings)) {
                Cookies.remove(cookieName);
            } else {
                Cookies.set(cookieName, JSON.stringify(settings), {expires: 365});
            }
            $(modalSelector).modal('toggle');
            location.reload();
        },
        changeVisibility: function (column) {
            var tbl = $('table.dataTable');
            var amount = tbl.find('th').length -1;
            if (column < 0 || column >= amount) {
                return;
            }
            var header = $(tbl.find('th').get(column));
            if (header.css("display") === "none") {
                header.show();
                tbl.find('tr').each(function(i){
                    $($(this).find('td').get(column)).show('ease');
                });
            } else {
                header.hide();
                tbl.find('tr').each(function(i){
                    $($(this).find('td').get(column)).hide('ease');
                });
            }
        }

    };

});
