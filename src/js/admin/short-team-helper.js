/**
 * Description
 */


(function ($) {
  'use strict';

  $(document).ready(function(){

    $('#sk-replacements').on('click', '.save-team-short, .delete-team-short', function (e) {
      e.preventDefault();
      var $row = $(this).parents('tr');
      var $form = $row.parents('form');
      var $input = $row.find('.team-short-input');
      var $orig = $input.next();
      var newValue;
      var emptyInput = false;
      if ( $(this).hasClass('save-team-short') ) {
        newValue = $input.val();
      } else {
        newValue = '';
        emptyInput = true;
      }
      $.post( $form.attr('data-endpoint'), {
        action: 'sk_team_short_name',
        _wpnonce: $form.attr('data-nonce'),
        orig: $orig.val(),
        replace: newValue
      }).done(function(result){
        $row.effect('highlight', {}, 3000);
        var $span = $('<span style="color: green;"></spans>');
        $span.text(result.message);
        if(emptyInput){
          $input.val('');
        }
        $orig.after($span);
        setTimeout(function(){
          $span.remove();
        }, 3000);
      }).fail(function(){
        alert('保存に失敗しました。やり直してください。');
      });
    });
  });

})(jQuery);
