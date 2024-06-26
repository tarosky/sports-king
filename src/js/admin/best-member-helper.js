/**
 * Description
 */

(function ($) {
  'use strict';

  $(document).ready(function(){

    var $container = $('.sk_best_members'),
      $list = $container.find('.sk_best_members__list');

    function update(){
      // 数を数える
      var length = $list.find('li').length;
      if ( length ) {
        $list.removeClass('sk_best_members__list--empty');
      }else{
        $list.addClass('sk_best_members__list--empty');
      }
      // ソート順
      $list.find('li').each(function(index, row){
        $(row).find('input').each(function(i, input){
          $(input).attr('name', $(input).attr('name').replace(/\[[0-9]+]/, function(){
            return '[' + ( index ) + ']';
          }));
        });
      });
    }

    // 検索ボタン
    $('.sk_best_members__search').each(function(index, input){
      $(input).autocomplete({
        source: $(input).attr('data-endpoint'),
        delay: 1500,
        focus: function(){
          return false;
        },
        select: function (event, ui) {
          var $row = append();
          $row.find('.sk_best_members__id').val(ui.item.id);
          $row.find('.sk_best_members__image').append( ui.item.image_tag );
          $row.find('.sk_best_members__team').text( ui.item.team );
          $row.find('.sk_best_members__title').text(ui.item.value);
          $row.find('.sk_best_members__position').text( ui.item.position );
          $(input).val('');
          update();
          return false;
        }
      });
    });

    /**
     * Append Item
     *
     * @returns {*}
     */
    function append(){
      return $($('.sk_best_members__tpl').html())
        .appendTo($list)
        .effect('highlight', {}, 500);
    }

    // 削除ボタン
    $container.on('click', '.sk_best_members__delete', function(e){
      e.preventDefault();
      $(this).parents('li').remove();
      update();
    });

    // ソートボタン
    $container.on('click', '.sk_best_members__up', function(e){
      e.preventDefault();
      var $tr = $(this).parents('li');
      var $prev = $tr.prev('li');
      $prev.before($tr);
      $tr.effect('highlight', {}, 500);
      update();
    });
    $container.on('click', '.sk_best_members__down', function(e){
      e.preventDefault();
      var $tr = $(this).parents('li');
      var $prev = $tr.next('li');
      $prev.after($tr);
      $tr.effect('highlight', {}, 500);
      update();
    });
  });

})(jQuery);
