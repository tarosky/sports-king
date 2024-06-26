/**
 * 関連する選手・チーム・試合を紐づけるエディター用ヘルパー
 *
 *
 * @deps jquery, jquery-tokeninput
 */

(function ($) {
  'use strict';

  $(document).ready(function(){

    $('.sk_relation__input').each(function(index, input){
      var postType = $(input).attr('id' ).replace( 'sk-relation-', '' );

      // チーム・プレイヤーの選択肢を出力
      var setTeamPlayerOptions = function( change ) {
        setTimeout( function() {
          var $pullDown = $( '#primary-' + postType );
          var curValue = $pullDown.val();
          var options = [];
          options.push( $( '<option class="no-primary" value="0">選択しない</option>' ) );
          if ( '0' === curValue ) {
            options[ 0 ].selected = true;
          }
          var values = $( '#sk-relation-' + postType ).tokenInput( 'get' );
          values.forEach( function ( value ) {
            var option = $( '<option value="' + value.id + '">' + value.name + '</option>' );
            options.push( option );
          } );
          $pullDown.empty().append( options );
          // 選択肢が1つしかなく、0だった場合は明示的に選択
          if ( 1 === values.length && '0' === curValue && change ) {
            curValue = values[0].id;
          }
          $pullDown.val( curValue );
          $pullDown.trigger( 'change' );
        }, 100 );
      };

      $(input).tokenInput($(input).attr('data-endpoint'), {
        prePopulate: window.SkRelation[$(input).attr('data-type')],
        theme: 'facebook',
        preventDuplicates: true,
        onReady: function() {
          setTeamPlayerOptions( false );
        },
        onAdd: function() {
          setTeamPlayerOptions( true );
        },
        onDelete: function() {
          setTeamPlayerOptions( true );
        },
      });
    });

    // 試合を設定する
    $('input[name=sk-search-abroad]:radio').change(function(){
        switch( $(this).val() ) {
          case '0' :
            $( '.sk-search__paragraph.league-japan' ).removeClass('hidden');
            $( '.sk-search__paragraph.league-world' ).addClass('hidden')
            break;
          case '1' :
            $( '.sk-search__paragraph.league-japan' ).addClass('hidden');
            $( '.sk-search__paragraph.league-world' ).removeClass('hidden')
            break;
        }
    });

    /**
     * クリアする
     */
    function clearRelation(){
      var $status = $('.sk_match__status');
      $('input[name=match-should-delete]').val('del');
      $('input[name=match-block-id]').val('');
      $status.empty();
      $status.append('<span class="sk_match__name--no">指定なし</span>');
    }

    /**
     * 試合結果をセットする
     * @param match
     */
    function setMatch(match){
      // 削除フラグを消す
      $('input[name=match-should-delete]').val('');
      $('input[name=match-block-id]').val(match.id);
      $('input[name=match-abroad]').val(match.abroad ? 1 : 0);
      // ステータスを追加
      $('.sk_match__status').empty().append('<span class="sk_match__name">' + match.label + '<a href="#">&times;</a></span>');
    }

    var $form = $('#sk-match-dialog').dialog({
      autoOpen: false,
      modal: true,
      width: 420,
      buttons: {
        "探す": function(){
          var query = $form.find('input[name=sk-search-text]').val();
          var abroad = $form.find('input[name=sk-search-abroad]:checked').val();
          var year = $form.find('select[name=sk-search-year]').val();
          var month = $form.find('select[name=sk-search-month]').val();
          var day = $form.find('select[name=sk-search-day]').val();
          var league = $form.find('input[name=sk-search-league]:checked').val();
          if( ! ( year && month ) ){
            alert('年月を指定してください');
            return false;
          }
          $(this).find('.sk-search__result').empty().append('<li class="sk-search__item--loading">検索中……（最大20件）</li>');
          $.get($(this).attr('data-endpoint'), {
            action: 'sk_match_search',
            s: query,
            abroad: abroad,
            year: year,
            month: month,
            day: day,
            league: league
          }).done(function(result){
            var $container = $form.find('.sk-search__result');
            $container.empty();
            if ( ! result.length ) {
              $container.append('<p class="descriptipon" style="color: red; text-align: center;">見つかりませんでした。</p>');
            } else {
              $.each(result, function (index, elt) {
                var $li = $('<li class="sk-search__item"><a class="sk-search__fix button">決定</a><span class="sk_search__item--title"></span></li>');
                $li.find('span').html(elt.label);
                $li.on('click', 'a', function (e) {
                  e.preventDefault();
                  setMatch(elt);
                  $form.dialog('close');
                });
                $('.sk-search__result').append($li);
              });
            }
          }).fail(function(){
            alert('検索に失敗しました。');
          });
        },
        "キャンセル": function(){
          $(this).dialog('close');
        }
      }
    });

    $('.sk_match__open').click(function(e){
      e.preventDefault();
      $form.dialog('open');
    });

    $('.sk_match__status').on('click', 'a', function(e){
      e.preventDefault();
      clearRelation();
    });

    //
    // パンクズリストの入力サポート
    //
    var showPankuzu = function(){
      var trails = [];
      [ 'league', 'team', 'player' ].forEach( function( key ) {
          var $input = $( 'select[name="primary-' + key + '"]' );
          if ( parseInt( $input.val() ) > 0 ) {
            trails.push( $input.find( 'option:selected' ).text() );
          }
      } );
      if ( ! trails.length ) {
        // なにも設定されていなければ
        trails.push( 'カテゴリー' );
      }

      trails.push( '記事タイトル' );
      $( '#sk-breadcrumb-preview' ).text( trails.join( ' > ' ) );
      $( '.sk_content-structure' ).effect( 'highlight', { color: '#ff9' }, 1000 );
    };

    // 初期値で出力
    showPankuzu();

    // リーグが変更されたら自動で出力
    $( 'input[name="tax_input[league][]"]' ).click( function( e ) {
      var leagues = {};
      $( '#taxonomy-league input:checked' ).each( function( index, input ) {
            leagues[ input.value ] = $( input ).parent().text().trim();
      } );
      var league_terms = [];
      for ( var id in leagues ) {
        if ( Object.hasOwn( leagues, id ) ) {
          league_terms.push( {
            id: id,
            label: leagues[ id ],
          } );
        }
      }
      if ( league_terms.length === 1 ) {
        // 1つなので自動で決定できる
        $( 'select[name="primary-league"] option[value="' + league_terms[0].id + '"]' ).attr( 'selected', true );
        showPankuzu();
      }
    } );
    // リーグとチームが変更されたらパンクズを変更
    $( '#primary-team, #primary-player' ).change( function() {
      showPankuzu();
    } );


    $( '#sk-breadcrumb-changer' ).click( function( e ) {
      e.preventDefault();
      $( '.sk_content-structure-body' ).toggleClass( 'is-open' );
    } );
  });


})(jQuery);
