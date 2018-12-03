/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/** global: Cookies */
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
            $(modalSelector).find('form').each(
                function() {
                    var settings = {};
                    var cookieName = $(this).attr('name');
                    $(this).find('input:checkbox').each(
                        function () {
                            settings[$(this).attr('name')] = $(this).is(':checked');
                        }
                    );
                    if (jQuery.isEmptyObject(settings)) {
                        Cookies.remove(cookieName);
                    } else {
                        Cookies.set(cookieName, JSON.stringify(settings), {expires: 365});
                    }
                }
            );
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
                header.show('ease');
                tbl.find('tr').each(function(){
                    $($(this).find('td').get(column)).show('ease');
                });
            } else {
                header.hide('ease');
                tbl.find('tr').each(function(){
                    $($(this).find('td').get(column)).hide('ease');
                });
            }
        }

    };

});
