!function(e){"use strict";function t(){a.ad=!!e('input[name="_is_ad"]').attr("checked"),a.ad?e(".sk_delivery__buttons").addClass("sk_delivery__ad"):e(".sk_delivery__buttons").removeClass("sk_delivery__ad"),i()}function i(){var t="",i=!1,d=e(".sk_delivery__status");a.ad?t="記事広告":(t=SkDeliveryHelper[a.delivery],""!==a.delivery&&(i=!0)),i?d.addClass("sk_delivery__status--active"):d.removeClass("sk_delivery__status--active"),d.text(t)}var a={ad:!1,delivery:""};e(document).ready(function(){e('input[name="_is_ad"]').click(t),e(".sk_delivery__buttons input[name=yahoo_upload]").each(function(t,i){if("checked"==e(i).attr("checked"))return a.delivery=e(i).val(),!1}),e(".sk_delivery__buttons").on("click","input[type=radio]",function(){a.delivery=e(this).val(),i()}),t()})}(jQuery);