/**
 * Description
 */

(function ($) {
  'use strict';

  $(document).ready(function(){

    var $container = $('.sk_recommends'),
      $list = $container.find('.sk_recommends__list');

    function update(){
      // 数を数える
      var length = $list.find('li').length;
      if ( length ) {
        $list.removeClass('sk_recommends__list--empty');
      }else{
        $list.addClass('sk_recommends__list--empty');
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
    $('.sk_recommends__search').each(function(index, input){
      $(input).autocomplete({
        source: $(input).attr('data-endpoint'),
        delay: 1500,
        focus: function(){
          return false;
        },
        select: function (event, ui) {
          var $row = append();
          $row.find('.sk_recommends__title').val(ui.item.value);
          $row.find('.sk_recommends__url').val(ui.item.url);
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
      return $($('.sk_recommends__tpl').html())
        .appendTo($list)
        .effect('highlight', {}, 500);
    }

    // 自動追加
    $('.sk_recommends__auto').click(function(e){
      e.preventDefault();
      $.get( $(this).attr('data-endpoint') , {
        action: 'sk_recommend_auto'
      }).done(function(result){
        $.each(result, function(index, item){
          var $li = append();
          $li.find('.sk_recommends__title').val(item.title);
          $li.find('.sk_recommends__url').val(item.url);
        });
        update();
      }).fail(function(){
        window.alert('データの取得に失敗しました。');
      });
    });

    // 追加ボタン
    $('.sk_recommends__add').click(function(e){
      e.preventDefault();
      append();
      update();
    });

    // 削除ボタン
    $container.on('click', '.sk_recommends__delete', function(e){
      e.preventDefault();
      $(this).parents('li').remove();
      update();
    });

    // ソートボタン
    $container.on('click', '.sk_recommends__up', function(e){
      e.preventDefault();
      var $tr = $(this).parents('li');
      var $prev = $tr.prev('li');
      $prev.before($tr);
      $tr.effect('highlight', {}, 500);
      update();
    });
    $container.on('click', '.sk_recommends__down', function(e){
      e.preventDefault();
      var $tr = $(this).parents('li');
      var $prev = $tr.next('li');
      $prev.after($tr);
      $tr.effect('highlight', {}, 500);
      update();
    });
    
    var $image_container = $('.sk_recommends_image'),
      $image_list = $image_container.find('.sk_recommends_image__list');
    function recommendimage_update(){
      // 数を数える
      var length = $image_list.find('li').length;
      if ( length ) {
        $image_list.removeClass('sk_recommends_image__list--empty');
      }else{
        $image_list.addClass('sk_recommends_image__list--empty');
      }
      // ソート順
      $image_list.find('li').each(function(index, row){
        $(row).find('input').each(function(i, input){
          $(input).attr('name', $(input).attr('name').replace(/\[[0-9]+]/, function(){
            return '[' + ( index ) + ']';
          }));
        });
      });
    }
    // 検索ボタン
    $('.sk_recommends_image__search').each(function(index, input){
      $(input).autocomplete({
        source: $(input).attr('data-endpoint'),
        delay: 1500,
        focus: function(){
          return false;
        },
        select: function (event, ui) {
          var $row = recommendimage_append();
          $row.find('.sk_recommends_image__title').val(ui.item.value);
          $row.find('.sk_recommends_image__url').val(ui.item.url);
          $(input).val('');
          recommendimage_update();
          return false;
        }
      });
    });
    /**
     * Append Item
     *
     * @returns {*}
     */
    function recommendimage_append(){
      return $($('.sk_recommends_image__tpl').html())
        .appendTo($image_list)
        .effect('highlight', {}, 500);
    }
    // 自動追加
    $('.sk_recommends_image__auto').click(function(e){
      e.preventDefault();
      $.get( $(this).attr('data-endpoint') , {
        action: 'sk_recommend_image_auto'
      }).done(function(result){
        $.each(result, function(index, item){
          var $li = recommendimage_append();
          $li.find('.sk_recommends_image__title').val(item.title);
          $li.find('.sk_recommends_image__url').val(item.url);
        });
        recommendimage_update();
      }).fail(function(){
        window.alert('データの取得に失敗しました。');
      });
    });
    // 追加ボタン
    $('.sk_recommends_image__add').click(function(e){
      e.preventDefault();
      recommendimage_append();
      recommendimage_update();
    });
    // 削除ボタン
    $image_container.on('click', '.sk_recommends_image__delete', function(e){
      e.preventDefault();
      $(this).parents('li').remove();
      recommendimage_update();
    });
    // ソートボタン
    $image_container.on('click', '.sk_recommends_image__up', function(e){
      e.preventDefault();
      var $tr = $(this).parents('li');
      var $prev = $tr.prev('li');
      $prev.before($tr);
      $tr.effect('highlight', {}, 500);
      recommendimage_update();
    });
    $image_container.on('click', '.sk_recommends_image__down', function(e){
      e.preventDefault();
      var $tr = $(this).parents('li');
      var $prev = $tr.next('li');
      $prev.after($tr);
      $tr.effect('highlight', {}, 500);
      recommendimage_update();
    });

  });

})(jQuery);
