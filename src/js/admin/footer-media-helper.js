/**
 * Description
 */

/*global hoge: true*/

(function ($) {

  $(document).ready(function () {
    // 左右
    $('.media-editor').on('click', '.moveLeft, .moveRight', function(e){
      e.preventDefault();
      var $container = $(this).parents('.media-editor__wrap');
      if($(this).hasClass('moveLeft')){
        $container.prev('.media-editor__wrap').before($container);
      }else {
        $container.next('.media-editor__wrap').after($container);
      }
      $container.parent('.media-editor__line').effect('highlight', {}, 500);
    });


    // 保存
    $('#media-submit').click(function(e){
      e.preventDefault();
      if( $(this).attr('disabled') ){
        return false;
      }
      if ( window.confirm('保存してよろしいですか？ この変更はすぐに反映されます。') ) {
        var postData = [];
        var errors = [];
        $('.media-editor__line').each(function(index, line){
          var data = [];
          $(line).find('.media-editor__wrap').each(function(i, wrap){
            switch( $(wrap).attr('data-type') ){
              case 'broken':
                errors.push('無効な情報が含まれています。削除してください。');
                return false;
                break;
              case 'link':
                var link = {};
                $(wrap).find('.media-editor__input').each(function(j, input){
                  var value = $(input).val();
                  var type  = $(input).attr('name');
                  link[$(input).attr('name')] = value;
                });
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
