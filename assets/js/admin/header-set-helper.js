!function(e){e(document).ready(function(){e("#header-set-submit").click(function(t){if(t.preventDefault(),e(this).attr("disabled"))return!1;if(window.confirm("保存してよろしいですか？ この変更はすぐに反映されます。")){var a=[],n=[];if(e(".media-editor__line").each(function(t,i){var r=[];e(i).find(".media-editor__wrap").each(function(t,a){switch(e(a).attr("data-type")){case"broken":return n.push("無効な情報が含まれています。削除してください。"),!1;case"link":var i={};e(a).find(".media-editor__input").each(function(t,a){var n=e(a).val(),r=e(a).attr("name");"newly"==r&&(n=e(a).prop("checked")?"1":"0"),i[e(a).attr("name")]=n}),r.push(i)}}),a.push(r)}),n.length)window.alert(n.join("\n"));else{var i=e(this);e(this).attr("disabled",!0),e.post(e(this).attr("data-endpoint"),{data:a}).done(function(e){window.alert(e.message)}).fail(function(e){window.alert(e.responseJSON?e.responseJSON.message:"保存に失敗しました。")}).always(function(){i.attr("disabled",!1)})}}})})}(jQuery);