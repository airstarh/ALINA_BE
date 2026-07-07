const ALINA = {
  body:  $("body"),
  Utils: {
    Date: {
      toUnixTimeStampInSeconds: function (d) {
        var v = new Date(d).getTime() / 1000;
        return v;
      },
    },
  },
  applyUI: function () {
    //////////////////////////////////////////////////
    //region jQuery UI
    var $datepicker = $(".js-datepicker");
    $.each($datepicker, function (i, el) {
      //var $el = $(this); // This works too
      var $el = $(el);
      var v   = $el.val();
          v   = new Date(v * 1000);
      //##########
      var altfield  = $el.data("altfield");
          altfield  = "#" + altfield;
      var $altfield = $(altfield);
      var $dp       = $el.datepicker({
        altFormat:       "@",
        altField:        altfield,
        dateFormat:      "yy-mm-dd",
        changeMonth:     true,
        changeYear:      true,
        showWeek:        true,
        firstDay:        1,
        showButtonPanel: true,
        yearRange:       "1900:2100",
        onSelect:        function (val, ctx) {
          $altfield.val(ALINA.Utils.Date.toUnixTimeStampInSeconds(val));
        },
      });
      $dp.datepicker("setDate", v);
      $altfield.val(ALINA.Utils.Date.toUnixTimeStampInSeconds(v));
      //$el.datepicker( "option", "altFormat", "yy-mm-dd" );
    });
    //endregion jQuery UI
    //////////////////////////////////////////////////
    //region HashTags
    const regexHashTagList = /(^|\W)#([a-zA-Zа-яА-Я_]+[0-9\w-]*)/gim;
    const txt              = `$1<a href="/#/?txt=%23$2">#$2</a>`;
    const allContentClass  = ".ck-content";
    const $allContent      = $(allContentClass);
    $.each($allContent, function (i, el) {
      const $el  = $(el);
      let   html = $el.html();
            html = html.replace(regexHashTagList, txt);
      $el.html(html);
    });
    //endregion HashTags
    //////////////////////////////////////////////////
  },
};
$(document).ready(function () {
  window.ALINA = ALINA;
});
