/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$(document).ready(function () {

    //$('.navbar-form select').selectpicker({});

    // DateRange - TODO improve me, so users can type without reloading in between
    $('.toolbar form input').change(function (event) {
        $('.toolbar form').submit();
    });

    $('.toolbar form select').change(function (event) {
        switch (event.target.id) {
            case 'customer':
                $('.toolbar form select#project').val('');
                if ($('.toolbar form select#activity').find(':selected').attr('data-global') == "false") {
                    $('.toolbar form select#activity').val('');
                }
                break;
            case 'project':
                if ($('.toolbar form select#activity').find(':selected').attr('data-global') == "false") {
                    $('.toolbar form select#activity').val('');
                }
                break;
        }
        $('.toolbar form').submit();
    });

});
