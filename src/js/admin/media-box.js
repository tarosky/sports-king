/**
 * Media box tab selector
 */
jQuery(document).ready(function ($) {

  function activate($target) {
    var current = $target.find('input:checked').val();
    $target.nextAll('.freeInput__tab').each(function (index, container) {
      if (current === $(container).attr('data-type')) {
        $(container).css('display', 'block');
      } else {
        $(container).css('display', 'none');
      }
    });
  }

  // Activate tab
  $('.freeInput__selector').each(function (index, elt) {

    activate($(elt));

    $(elt).find('input[type=radio]').click(function () {
      activate($(elt));
    });
  });

  // Handle List
  $('.freeInput__list-add').click(function(e){

    e.preventDefault();

    var $container = $(this).parents('.freeInput__tab');
    var $template = $($container.find('.freeInput__template').html());

    $container.find('.freeInput__list')
      .effect('highlight', {}, 1000)
      .append($template);
    $template.find('.url-suggest').trigger('uiReady.suggest');

  });


  $('.freeInput__list')
    .on('click', '.close', function(e){
    // 削除ボタン
      e.preventDefault();
      $(this).parents('li').remove();
    })
    // ソート
    .sortable({
      axis: 'y',
      handle: '.drag',
      placeholder: 'list-placeholder'
    })
    .on('click', '.drag', function(e){
      e.preventDefault();
    });



});
