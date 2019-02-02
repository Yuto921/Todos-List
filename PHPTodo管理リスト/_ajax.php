<?php

session_start();

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/Todo.php');

// Todoクラスのインスタンスを作ってpost()というメソッドを呼び出す
$todoApp = new \MyApp\Todo();

// POSTされたときだけ処理を行う
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // 配列を結果で返すため$resで受け取る
    $res = $todoApp->post();
    // jQueryで扱いやすいように、データ自体はjson形式というJavaScriptのオブジェクト形式で返す
    header('Content-Type: application/json');
    // 実際のデータを送信
    echo json_encode($res);
    // ここで処理を終える
    exit;
  } catch (Exception $e) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo $e->getMessage();
    exit;
  }
}