$(document).ready(function() {

    // $.AdminLTE.pushMenu.expandOnHover();

    (function( $ ) {

        var pubSub = $({});

        var methods = {
            init : function(options) {
                $.fn.kimai.settings = $.extend( {}, $.fn.kimai.defaults, options );

                // ask before a delete call is executed
                $('a.btn-trash').click(function (event) {
                    return confirm($.fn.kimai.settings['confirmDelete']);
                });
            },
            hook: function(name) {
                alert('Hook: ' + name);
            },
            subscribe: function() {
                pubSub.on.apply(pubSub, arguments);
            },
            unsubscribe: function() {
                pubSub.off.apply(pubSub, arguments);
            },
            publish: function() {
                pubSub.trigger.apply(pubSub, arguments);
            },
            pauseRecord: function(selector) {
                $(selector + ' .pull-left i').hover(function () {
                    var link = $(this).parents('a');
                    link.attr('href', link.attr('href').replace('/stop', '/pause'));
                    $(this).removeClass('fa-stop-circle').addClass('fa-pause-circle').addClass('text-orange');
                },function () {
                    var link = $(this).parents('a');
                    link.attr('href', link.attr('href').replace('/pause', '/stop'));
                    $(this).removeClass('fa-pause-circle').removeClass('text-orange').addClass('fa-stop-circle');
                });
            }
        };

        $.fn.kimai = function(options) {
            if (methods[options]) {
                return methods[options].apply( this, Array.prototype.slice.call(arguments, 1));
            } else if (typeof options === 'object') {
                return methods.init.apply( this, arguments );
            }
            return this;
        };

        // default values
        $.fn.kimai.defaults = {
            baseUrl: '/',
            imagePath: '/images',
            confirmDelete: 'Really delete?'
        };

        // once initialized, here are all values
        $.fn.kimai.settings = {};

    }( jQuery ));

});
