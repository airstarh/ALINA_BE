$(document).ready(function () {
    window.ALINA = window.ALINA || {};
    window.ALINA.body = ALINA.body || $('body');

    window.ALINA.applyUI = function () {
        //////////////////////////////////////////////////
        //region jQuery UI
        var $datepicker = $('.datepicker');
        $.each($datepicker, function() {
            var $el = $(this);
            var v = $el.val();
            v = new Date(v*1000);
            $el.datepicker({
                'dateFormat': 'yy-mm-dd'
            });
            $el.datepicker( "setDate", v );
        });
        //endregion jQuery UI
        //////////////////////////////////////////////////
    }
});
