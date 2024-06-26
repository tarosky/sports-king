/**
 * 置換データを管理する
 */

(function ($) {
  'use strict';

  $(document).ready(function(){
    var $form = $('#sk-replacements');
    var endpoint = $form.attr('data-endpoint');
    var nonce = $form.attr('data-nonce');

    // 保存ボタンを監視
    $form.on('click', '.save-repl', function(e){
      e.preventDefault();
      var $tr = $(this).parents('tr');
      var from = $tr.find('.sk-repl-orig').val();
      var to   = $tr.find('.sk-repl-replaced').val();
      var id = $(this).attr('data-id');
      $.post(endpoint, {
        action: 'sk_replace_edit',
        type: 'save',
        _wpnonce: nonce,
        id: id,
        from: from,
        to: to
      }).done(function(){
        $tr.effect('highlight', {}, 500);
      }).fail(function(){
        alert('保存に失敗しました。');
      });
    });
    // 削除ボタン
    $form.on('click', '.delete-repl', function(e){
      e.preventDefault();
      if( ! window.confirm('本当に削除してよろしいですか？ この操作は取り消せません') ){
        return;
      }
      var $tr = $(this).parents('tr');
      var id = $(this).attr('data-id');
      $.post(endpoint, {
        action: 'sk_replace_edit',
        type: 'delete',
        _wpnonce: nonce,
        id: id
      }).done(function(){
        $tr.effect('highlight', {}, 500, function(){
          $tr.remove();
        });
      }).fail(function(){
        alert('削除に失敗しました。');
      });
    });
    // 新規作成
    $('#sk-replacement-add').submit(function (e) {
      e.preventDefault();
      var type = $('#sk-type').val();
      var from = $('#sk-orig').val();
      var to = $('#sk-replaced').val();
      if (from.length * to.length < 1) {
        alert('置換用文字列が選択されていません。');
      } else {
        $.post(endpoint, {
          action   : 'sk_replace_edit',
          type     : 'create',
          _wpnonce : nonce,
          repl_type: type,
          repl_from: from,
          repl_to  : to
        }).done(function () {
          $('#sk-replacement-add').effect('highlight', {}, 500, function () {
            window.location.reload();
          });
        }).fail(function () {
          alert('作成に失敗しました。');
        });
      }
    });
  });

})(jQuery);
