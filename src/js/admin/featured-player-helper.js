/**
 * Description
 */

(function ($) {
  'use strict';

  $(document).ready(function(){

    var $input = $('#sk-featured-player-search');
    var $list = $('.sk_featured_players__list');
    var template = $('.sk_featured_players__template').html();

    $input.autocomplete({
      source: $input.attr('data-href'),
      delay: 1500,
      focus: function(){
        return false;
      },
      select: function( event, ui ){
        var $li = $( template );
        $li.find('input[type=hidden]').val( ui.item.id );
        $li.find('.sk_featured_players__name').attr('href', ui.item.link).text(ui.item.name)
          .next('small').text(ui.item.team);
        $list.append($li).effect('highlight', {}, 1000);
      }
    }).autocomplete('instance')._renderItem = function (ul, item) {
      return $('<li>')
        .append('<a>' + item.name + '（' + item.team + '）' +  '</a>')
        .appendTo(ul);
    };

    $list.on('click', 'a.close', function(e){
      e.preventDefault();
      if( window.confirm( '削除してよろしいですか？' ) ){
        $(this).parents('li').remove();
        $list.effect('highlight', {}, 1000);
      }
    });

    $list.sortable({
      axis: 'y',
      handle: '.drag',
      placeholder: 'sk_featured_players__item--sorting'
    });
  });

})(jQuery);
