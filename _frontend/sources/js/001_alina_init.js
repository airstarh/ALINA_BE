$(document).ready(function () {
    window.ALINA = window.ALINA || {};
    window.ALINA.body = ALINA.body || $('body');

    window.ALINA.applyUI = function () {
        //////////////////////////////////////////////////
        //region jQuery UI
        var $datepicker = $('.datepicker');
        $.each($datepicker, function () {
            var $el = $(this);
            var v = $el.val();
            v = new Date(v * 1000);
            $el.datepicker({
                'dateFormat': 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                showWeek: true,
                firstDay: 1,
                showButtonPanel: true,
                yearRange: "-5000:5000"

            });
            $el.datepicker("setDate", v);
        });
        //endregion jQuery UI
        //////////////////////////////////////////////////
    }
});
