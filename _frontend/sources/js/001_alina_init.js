$(document).ready(function () {
    window.ALINA         = window.ALINA || {};
    window.ALINA.body    = ALINA.body || $('body');
    window.ALINA.Utils   = {
        Date: {
            toUnixTimeStampInSeconds: function (d) {
                var v = new Date(d).getTime() / 1000;
                return v;
            }
        }
    };

    window.ALINA.applyUI = function () {
        //////////////////////////////////////////////////
        //region jQuery UI
        var $datepicker = $('.js-datepicker');
        $.each($datepicker, function (i, el) {
            //var $el = $(this); // This works too
            var $el       = $(el);
            var v         = $el.val();
            v             = new Date(v * 1000);
            //##########
            var altfield  = $el.data('altfield');
            altfield      = "#" + altfield;
            var $altfield = $(altfield);
            var $dp       = $el.datepicker({
                altFormat:       "@",
                altField:        altfield,
                dateFormat:      'yy-mm-dd',
                changeMonth:     true,
                changeYear:      true,
                showWeek:        true,
                firstDay:        1,
                showButtonPanel: true,
                yearRange:       "1900:2100",
                onSelect:        function (val, ctx) {
                    $altfield.val(window.ALINA.Utils.Date.toUnixTimeStampInSeconds(val));
                }

            });
            $dp.datepicker("setDate", v);
            $altfield.val(window.ALINA.Utils.Date.toUnixTimeStampInSeconds(v));
            //$el.datepicker( "option", "altFormat", "yy-mm-dd" );
        });
        //endregion jQuery UI
        //////////////////////////////////////////////////
    }
});
