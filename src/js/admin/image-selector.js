/**
 * Description
 */

/*global wp: false*/

jQuery(document).ready(function ($) {

  'use strict';

  var mediaFrame;

  /**
   * 画像IDをアップデートする
   * @param {jQuery} $container
   */
  function updateImageIds($container) {
    var ids = [];
    $container.find('.image-selector-picture').each(function (i, pic) {
      ids.push(parseInt($(pic).attr('data-id'), 10));
    });
    $container.find('.image-selector-input').val(ids.join(','));
  }

  /**
   * 画像IDをチェックする
   * @param {jQuery} $container
   * @return {Boolean}
   */
  function imageLength($container) {
    var max = parseInt($container.find('.image-selector-input').attr('data-max'), 10);
    var length = $container.find('.image-selector-picture').length;
    return max > length;
  }

  var $parent;
//  $('.image-selector').each(function (index, parent) {


    $(document).on('click', '.image-selector-button', function (e) {
//    $(parent).on('click', '.image-selector-button', function (e) {

      e.preventDefault();

      $parent = $(this).parents('.image-selector');

      if (!imageLength($parent)) {
        return;
      }

      if (!mediaFrame) {

        mediaFrame = wp.media({
          className: 'media-frame taro-media-frame',
          frame    : 'select',
          multiple : false,
          title    : '使用する画像をアップロードまたは選択してください。',
          library  : {
            type: 'image'
          },
          button   : {
            text: '選択した画像を挿入'
          }
        });

        // 選択した場合のイベントをバインド
        mediaFrame.on('select', function () {
          // アタッチメントの情報を取得
          var src;
          var attachment = mediaFrame.state().get('selection').first().toJSON();
          var $image = $('<div class="image-selector-container"></div>');
          // アタッチメント情報を保存する
          if (attachment.sizes.thumbnail) {
            //サムネイルがあればその画像
            src = attachment.sizes.thumbnail.url;
          } else {
            //なければフルサイズを取得
            src = attachment.sizes.full.url;
          }

          $image.append('<img class="image-selector-picture" src="' + src + '" data-id="' + attachment.id + '" />')
            .append('<a class="button image-selector-delete" href="#">削除</a>');

          $parent.find('.image-selector-place-holder').before($image);
          updateImageIds($parent);
        });
      }

      // メディアフレームを開く
      mediaFrame.open();

    }).on('click', '.image-selector-delete', function (e) {
      e.preventDefault();
      $parent = $(this).parents('.image-selector');
      $(this).parents('.image-selector-container').remove();
      updateImageIds($parent);
    });

//  });

});
