/**
 * Description
 */

/* global SkDeliveryHelper: false */

(function ($) {

  'use strict';

  var status = {
    ad: false,
    delivery: ''
  };

  function isAd(){
    status.ad = !! $('input[name="_is_ad"]').attr('checked');
    if( status.ad ){
      $('.sk_delivery__buttons').addClass('sk_delivery__ad');
    }else{
      $('.sk_delivery__buttons').removeClass('sk_delivery__ad');
    }
    update();
  }

  function update(){
    var text = '';
    var active = false;
    var $container = $('.sk_delivery__status');
    if(status.ad){
      text = '記事広告';
    }else{
      text = SkDeliveryHelper[status.delivery];
      if( '' !== status.delivery ){
        active = true;
      }
    }
    if( active) {
      $container.addClass('sk_delivery__status--active');
    }else{
      $container.removeClass('sk_delivery__status--active');
    }
    $container.text(text);
  }

  $(document).ready(function(){

    $('input[name="_is_ad"]').click(isAd);

    $('.sk_delivery__buttons input[name=yahoo_upload]').each(function(index, input){
      if( 'checked' == $(input).attr('checked') ){
        status.delivery = $(input).val();
        return false;
      }
    });

    $('.sk_delivery__buttons').on('click', 'input[type=radio]', function(){
      status.delivery = $(this).val();
      update();
    });

    isAd();
  });

})(jQuery);
