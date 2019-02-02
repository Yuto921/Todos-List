$(function() {
  'use strict';

  $('#new_todo').focus();

  // update・・リスト状態の更新
  // チェックボックスがクリックされた時に処理を行う
  $('#todos').on('click', '.update_todo', function() {
    /* idを取得
       Todo の id = update_todo の要素の親要素の li の data 属性の id を引っ張ってくる
    */
    var id = $(this).parents('li').data('id');
    // ajax処理
    $.post('_ajax.php', {
      id: id,
      mode: 'update',
      token: $('#token').val()
      // resというオブジェクトを返して更新したTodoの状態を入れる
    }, function(res) {
      // Todoの状態に応じてdoneクラスをつけたりつけなかったりする
      if (res.state === '1') {
        $('#todo_' + id).find('.todo_title').addClass('done');
      } else {
        $('#todo_' + id).find('.todo_title').removeClass('done');
      }
    })
  });

  // delete
  $('#todos').on('click', '.delete_todo', function() {
    // idを取得
    var id = $(this).parents('li').data('id');
    // ajax処理
    if (confirm('are you sure?')) {
      $.post('_ajax.php', {
        id: id,
        mode: 'delete',
        token: $('#token').val()
      }, function() {
        $('#todo_' + id).fadeOut(800);
      });
    }
  });

  // create
  $('#new_todo_form').on('submit', function() {
    // titleを取得
    var title = $('#new_todo').val();
    // ajax処理
    $.post('_ajax.php', {
      title: title,
      mode: 'create',
      token: $('#token').val()
    }, function(res) {
      // liを追加
      var $li = $('#todo_template').clone();
      $li
        .attr('id', 'todo_' + res.id)
        .data('id', res.id)
        .find('.todo_title').text(title);
      // prependでliを一番上に追加
      $('#todos').prepend($li.fadeIn());
      // 連続で追加できるように#new_todoの値は空
      $('#new_todo').val('').focus();
    });
    // 画面の遷移を防ぐ
    return false;
  });

});