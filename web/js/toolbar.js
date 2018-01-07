$(document).ready(function () {

    $('.toolbar form select').change(function (event) {
        $('.toolbar form').submit();
    });

});
