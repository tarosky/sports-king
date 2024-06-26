/**
 * Description
 */

/*global hoge: true*/

(function ($) {

  $(document).ready(function () {

    // リンクを挿入する
    $('#pickup-editor-link').submit(function (e) {
      e.preventDefault();
      var index = parseInt($(this).find('select').val(), 10) - 1;
      $('.pickup-editor__line:nth(' + index + ')')
        .append($(this).find('.pickup-editor__template').html())
        .effect('highlight', {}, 500);
    });


    // インクリメンタルサーチ
    var $input = $('#pickup-editor-search');
    $input.autocomplete({
      source: $input.attr('data-action'),
      delay: 1500,
      focus: function(){
        return false;
      },
      select: function( event, ui ){
        var index = parseInt( $input.prevAll('select').val(), 10) - 1;
        $('.pickup-editor__line:nth(' + index + ')')
          .append(ui.item.template)
          .effect('highlight', {}, 500);
      }
    }).autocomplete('instance')._renderItem = function(ul, item){
      return $('<li>')
        .append('<a>' + item.name + '</a>')
        .appendTo(ul);
    };

    // 削除
    $('.pickup-editor').on('click', '.button-delete', function(e){
      e.preventDefault();
      $(this).parents('.pickup-editor__line').effect('highlight', {}, 500);
      $(this).parents('.pickup-editor__wrap').remove();
    });

    // 左右
    $('.pickup-editor').on('click', '.moveLeft, .moveRight', function(e){
      e.preventDefault();
      var $container = $(this).parents('.pickup-editor__wrap');
      if($(this).hasClass('moveLeft')){
        $container.prev('.pickup-editor__wrap').before($container);
      }else {
        $container.next('.pickup-editor__wrap').after($container);
      }
      $container.parent('.pickup-editor__line').effect('highlight', {}, 500);
    });

    // 上下
    $('.pickup-editor').on('click', '.moveUp, .moveDown', function(e){
      e.preventDefault();
      var $container = $(this).parents('.pickup-editor__wrap');
      var $line = $container.parents('.pickup-editor__line');
      if( $(this).hasClass('moveUp') ){
        $line.prev().append($container).effect('highlight', {}, 500);
      }else{
        $line.next().append($container).effect('highlight', {}, 500);
      }
    });

    // 保存
    $('#pickup-submit').click(function(e){
      e.preventDefault();
      if( $(this).attr('disabled') ){
        return false;
      }
      if ( window.confirm('保存してよろしいですか？ この変更はすぐに反映されます。') ) {
        var postData = [];
        var errors = [];
        $('.pickup-editor__line').each(function(index, line){
          var data = [];
          $(line).find('.pickup-editor__wrap').each(function(i, wrap){
            switch( $(wrap).attr('data-type') ){
              case 'broken':
                errors.push('無効な情報が含まれています。削除してください。');
                return false;
                break;
              case 'post':
                data.push($(wrap).find('input').val());
                break;
              case 'link':
                var link = {};
                $(wrap).find('.pickup-editor__input').each(function(j, input){
                  var value = $(input).val();
                  var type  = $(input).attr('name');
                  if( (('image' != type) && ('image_id' != type)) && ( !value || !value.length ) ){
                    errors.push( (index+1) + '列' + (i+1) + '番目のリンク情報が無効です。タイトル、URLが必要です。' );
                    return false;
                  }
                  link[$(input).attr('name')] = value;
                });
                link.external = !! $(wrap).find('input:checked').length;
                data.push(link);
                break;
              default:
                // Do nothing.
                break;
            }
          });
          postData.push(data);
        });
        if( errors.length ){
          window.alert( errors.join("\n") );
        }else{
          var $button = $(this);
          $(this).attr('disabled', true);
          $.post($(this).attr('data-endpoint'), {data: postData}).done(function(response){
            window.alert(response.message);
          }).fail(function(xhr){
            window.alert(xhr.responseJSON ? xhr.responseJSON.message : '保存に失敗しました。');
          }).always(function(){
            $button.attr('disabled', false);
          });
        }
      }
    });

  });


})(jQuery);
