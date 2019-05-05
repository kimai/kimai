/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] Toolbar: some helper scripts for data-table filter, toolbar and navigation
 */
$(document).ready(function () {

    // This catches all clicks on the pagination and prevents the default action, as we want to relad the page via JS
    $('body').on('click', 'div.navigation ul.pagination li a', function(event) {
        var $pager = $(".toolbar form input[name='page']");
        if ($pager.length === 0) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        var $urlParts = $(this).attr('href').split('/');
        var page = $urlParts[$urlParts.length-1];
        $pager.val(page);
        $pager.trigger('change');
        return false;
    });

    // Reset the page if any other value is changed, otherwise we might end up with a limited set
    // of data which does not support the given page - and it would be just wrong to stay in the same page
    $('.toolbar form input').change(function (event) {
        switch (event.target.id) {
            case 'page':
                break;
            default:
                $('.toolbar form input#page').val(1);
        }
        $.kimai.reloadDatatableWithToolbarFilter();
    });

    $('.toolbar form select').change(function (event) {
        var reload = true;
        switch (event.target.id) {
            case 'customer':
                if ($('.toolbar form select#project').length > 0) {
                    reload = false;
                }
                break;

            case 'project':
                if ($('.toolbar form select#activity').length > 0) {
                    reload = false;
                }
                break;
        }
        $('.toolbar form input#page').val(1);
        if (reload) {
            $.kimai.reloadDatatableWithToolbarFilter();
        }
    });

});
