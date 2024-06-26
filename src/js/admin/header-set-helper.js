/**
 * Description
 */

/*global hoge: true*/

(function ($) {

  $(document).ready(function () {
    // 保存
    $('#header-set-submit').click(function(e){
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
                  if ( type == 'newly' ) {
                    if ( $(input).prop( 'checked' ) ) {
                      value = '1';
                    } else {
                      value = '0';
                    }
                  }

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
