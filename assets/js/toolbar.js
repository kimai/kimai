/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$(document).ready(function () {

    $('.toolbar form input').change(function (event) {
        toolbarLoadDataAfterChange();
    });

    $('.toolbar form select').change(function (event) {
        switch (event.target.id) {
            case 'customer':
                $('.toolbar form select#project').val('');
                if ($('.toolbar form select#activity').find(':selected').attr('data-project')) {
                    $('.toolbar form select#activity').val('');
                }
                break;
            case 'project':
                if ($('.toolbar form select#activity').find(':selected').attr('data-project')) {
                    $('.toolbar form select#activity').val('');
                }
                break;
        }
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
