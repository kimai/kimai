/*!
 * Kimai Time-Tracking - toolbar.js
 *
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$(document).ready(function () {

    // DateRange - TODO improve me, so users can type without reloading in between
    $('.toolbar form input').change(function (event) {
        $('.toolbar form').submit();
    });

    $('.toolbar form select').change(function (event) {
        switch (event.target.id) {
            case 'customer':
                if ($(this).val() === '') {
                    $('.toolbar form select#project').parent().remove();
                } else {
                    $('.toolbar form select#project').val('');
                }
                $('.toolbar form select#activity').parent().remove();
                break;
            case 'project':
                if ($(this).val() === '') {
                    $('.toolbar form select#activity').parent().remove();
                } else {
                    $('.toolbar form select#activity').val('');
                }
                break;
        }
        $('.toolbar form').submit();
    });

});
