/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$(document).ready(function () {

    /* Submit the pagination including the toolbar filters */
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
