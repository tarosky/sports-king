/**
 * Description
 */

/*global SkUrlSuggest: false*/

(function ($) {

  'use strict';

  /**
   * 内部のキーが有効化どうかしらべる
   *
   * @param $input
   */
  function isInternal($input) {
    var str = $input.val();
    var home = $input.attr('data-internal');
    var $status = $input.nextAll('small');
    if (-1 < str.indexOf(home)) {
      $status.addClass('internal');
    } else {
      $status.removeClass('internal');
    }
  }

  /**
   * inputをオートコンプリートにする
   *
   * @param $elem
   */
  function autoCompletefy($elem) {
    $elem.autocomplete({
      minLength: 2,
      source   : function (request, response) {
        if( /^http/.test(request.term) ){
          // httpからはじまる場合は無駄だから検索しない
          response([]);
        }else{
          $.get(SkUrlSuggest.endpoint + '&q=' + request.term).done(function (result) {
            response(result.data);
          }).fail(function () {
            response([]);
          });
        }
      },
      select   : function (event, ui) {
        $(this).val(ui.item.url);
        isInternal($(this));
        return false;
      }
    }).autocomplete('instance')._renderItem = function (ul, item) {
      return $('<li>')
        .append('<a>' + item.title + '</a>')
        .appendTo(ul);
    };
  }

  // URLチェック
  $(document).on('click', '.url-suggest-button', function (e) {
    e.preventDefault();
    var url = $(this).prevAll('input').val();
    if (/^https?:\/\//.test(url)) {
      window.open(url, 'url-check');
    } else {
      alert('URLの形式が不正です');
    }
  });

  // 外部か否か
  $(document).on('keyup', '.url-suggest', function () {
    isInternal($(this));
  });

  // インクリメンタルサーチ
  $(document).on('uiReady.suggest', '.url-suggest', function () {
    autoCompletefy($(this));
  });

  // 初期化処理
  $(document).ready(function () {
    $('.url-suggest').each(function (i, input) {
      // 既存の要素の外部チェック
      isInternal($(input));
      // 既存の要素をオートコンプリート
      autoCompletefy($(input));
    });
  });


})(jQuery);
