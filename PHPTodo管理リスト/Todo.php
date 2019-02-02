<?php

/*
・CSRF対策・・セキュリティーチェック・データが改ざんされたフォームではなく、正しいフォームから送られたかどうかをチェックする
・Token(推測されにくい文字列)発行してSessionに格納
・フォームからもTokenを発行、送信
・Check・・処理の中でsessionの中のTokenとフォームから送られたTokenが同じかどうかをチェックすることで対策を施していく
*/

namespace MyApp;

class Todo {
  // DBを扱うのでprivateプロパティで$dbを設定
  private $_db;

  // コンストラタで$dbへの接続
  public function __construct() {
    $this->_createToken();

    // $dbへの接続は例外ありなのでtry・catch
    try {
      // $this->_dbでPDOのインスタンス作成
      $this->_db = new \PDO(DSN, DB_USERNAME, DB_PASSWORD);
      // データベース例外の扱い設定
      $this->_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    } catch (\PDOException $e) {
      echo $e->getMessage();
      exit;
    }
  }

  private function _createToken() {
    // もしsessionのTokenがなかったら作って格納しなさい
    if (!isset($_SESSION['token'])) {
      // 32桁の推測されにくい文字列
      $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(16));
    }
  }

  public function getAll() {
    // order by id descで常に新しいものを上に
    $stmt = $this->_db->query("select * from todos order by id desc");
    // 結果をオブジェクト形式で返す
    return $stmt->fetchAll(\PDO::FETCH_OBJ);
  }

  public function post() {
    $this->_validateToken();

    // post()には必ずmode(ajax処理のモード)が渡されるようになっているはず
    if (!isset($_POST['mode'])) {
      throw new \Exception('mode not set!');
    }

    // modeが渡ってきたらそれに応じた処理・・返り血は配列になるはずなのでreturn(switch文のbreakなし)
    switch ($_POST['mode']) {
      case 'update':
        return $this->_update();
      case 'create':
        return $this->_create();
      case 'delete':
        return $this->_delete();
    }
  }

  private function _validateToken() {
    // sessionやpostのチェック
    if (
      !isset($_SESSION['token']) ||
      !isset($_POST['token']) ||
      $_SESSION['token'] !== $_POST['token']
    ) {
      throw new \Exception('invalid token!');
    }
  }

  private function _update() {
    // idが渡ってきてなかったら例外を返す
    if (!isset($_POST['id'])) {
      throw new \Exception('[update] id not set!');
    }

    // トランザクション(同時にたくさんアクセスされた時にidがずれると困ってしまうので,確実に更新されたtodoの$stateが取得できるように)
    $this->_db->beginTransaction();

    /* idをもとにデータベースを更新
       stateが0の時は1に、1の時は0にしたいので、stateに1を足して2で割った余りで更新
       int型整数型の時はsprintf()の%dを使う
    */
    $sql = sprintf("update todos set state = (state + 1) %% 2 where id = %d", $_POST['id']);
    $stmt = $this->_db->prepare($sql);
    $stmt->execute();

    // 更新されたstateを返す
    $sql = sprintf("select state from todos where id = %d", $_POST['id']);
    $stmt = $this->_db->query($sql);
    $state = $stmt->fetchColumn();

    // トランザクションcommit
    $this->_db->commit();

    // 配列で返すためreturn
    return [
      'state' => $state
    ];
  }

  private function _create() {
    if (!isset($_POST['title']) || $_POST['title'] === '') {
      throw new \Exception('[create] title not set!');
    }

    $sql = "insert into todos (title) values (:title)";
    $stmt = $this->_db->prepare($sql);
    $stmt->execute([':title' => $_POST['title']]);

    // 返り値は、挿入されたレコードのid
    return [
      'id' => $this->_db->lastInsertId()
    ];
  }

  private function _delete() {
    if (!isset($_POST['id'])) {
      throw new \Exception('[delete] id not set!');
    }

    $sql = sprintf("delete from todos where id = %d", $_POST['id']);
    $stmt = $this->_db->prepare($sql);
    $stmt->execute();

    // 返り値は特にないので空の配列を渡す
    return [];
  }
}