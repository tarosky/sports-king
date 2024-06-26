/**
 * Description
 */

(function ($) {
  'use strict';

  $(document).ready(function(){

    var $input = $('#sk-related-media-search');

    $input.autocomplete({
      source: $input.attr('data-href'),
      delay: 1500,
      focus: function(){
        return false;
      },
      select: function( event, ui ){
        var $container = $('.sk_related_media__display');
        $container.find('input[type=hidden]').val( ui.item.id );
        $container.find('.sk_related_media__title').text(ui.item.name);
        $container.find('.sk_related_media__category').text(ui.item.media);
        $container.find('.sk_related_media__image').html(ui.item.image);
        $container.find('a.button').attr('disabled', false);
        $container.effect('highlight', {}, 1000);
      }
    }).autocomplete('instance')._renderItem = function (ul, item) {
      return $('<li>')
        .append('<a>' + item.name + '（' + item.media+ '）' +  '</a>')
        .appendTo(ul);
    };

    $('.sk_related_media__display').on('click', 'a.button', function(e){
      e.preventDefault();
      if( ( ! $(this).attr('disabled') ) && window.confirm( '削除してよろしいですか？' ) ){
        var $container = $(this).parents('.sk_related_media__display');
        $container.find('input[name=related_media_id]').val('');
        $container.find('.sk_related_media__image img').remove();
        $container.find('.sk_related_media__title').empty();
        $container.find('.sk_related_media__category').empty();
        $container.find('.button').attr('disabled', true);
        $container.effect('highlight', {}, 1000);
      }
    });

  });

})(jQuery);
