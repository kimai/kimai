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
        toolbarLoadDataAfterChange();
    });

    $('.toolbar form select').change(function (event) {
        $('.toolbar form input#page').val(1);
        toolbarLoadDataAfterChange();
    });

    function toolbarLoadDataAfterChange()
    {
        var $form = $('.toolbar form');
        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: $form.serialize(),
            success: function(html) {
                $('section.content').replaceWith(
                    $(html).find('section.content')
                );
            },
            error: function(xhr, err) {
                $form.submit();
            }
        });
    }

});
