$(document).ready(function() {

    (function( $ ) {

        var pubSub = $({});

        var methods = {
            init : function(options) {
                $.fn.kimai.settings = $.extend( {}, $.fn.kimai.defaults, options );
            },
            hook: function(name) {
                alert('Hook: ' + name);
                console.log($.fn.kimai.settings);
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
            ticktac: function(selector) {
                $(selector + ' img').hover(function () {
                    $(this).attr('src', $.fn.kimai.settings.imagePath + '/buzzer-on-hover.png');
                },function () {
                    $(this).attr('src', $.fn.kimai.settings.imagePath + '/buzzer-on.png');
                });
            }
        };

        $.fn.kimai = function(options) {
            console.log('#');
            console.log(options);
            console.log('#');
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
            imagePath: '/images'
        };

        // once initialized, here are all values
        $.fn.kimai.settings = {};

    }( jQuery ));

});
