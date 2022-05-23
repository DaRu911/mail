<?php
//セッションを開始
session_start();
 
//セッションIDを更新して変更（セッションハイジャック対策）
session_regenerate_id( TRUE );
 
//エスケープ処理やデータチェックを行う関数のファイルの読み込み
require 'libs/functions.php';
 
//NULL 合体演算子を使ってセッション変数を初期化（PHP7.0以降）
$name = $_SESSION[ 'name' ] ?? NULL;
$email = $_SESSION[ 'email' ] ?? NULL;
$email_check = $_SESSION[ 'email_check' ] ?? NULL;
$tel = $_SESSION[ 'tel' ] ??  NULL;
$subject = $_SESSION[ 'subject' ] ?? NULL;
$body = $_SESSION[ 'body' ] ?? NULL;
$error = $_SESSION[ 'error' ] ?? NULL;
 
//個々のエラーを NULL で初期化（PHP7.0以降）
$error_name = $error[ 'name' ] ?? NULL;
$error_email = $error[ 'email' ] ?? NULL;
$error_email_check = $error[ 'email_check' ] ?? NULL;
$error_tel = $error[ 'tel' ] ?? NULL;
$error_subject = $error[ 'subject' ] ?? NULL;
$error_body = $error[ 'body' ] ?? NULL;
 
//CSRF対策のトークンを生成
if ( !isset( $_SESSION[ 'ticket' ] ) ) {
  //セッション変数にトークンを代入（PHP7.0以降）
  $_SESSION[ 'ticket' ] = bin2hex(random_bytes(32));
}
//トークンを変数に代入（隠しフィールドに挿入する値）
$ticket = $_SESSION[ 'ticket' ];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>コンタクトフォーム</title>
<link href="../bootstrap.min.css" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
</head>
<body>
<div class="container">
  <h2>お問い合わせフォーム</h2>
  <p>以下のフォームからお問い合わせください。</p>
  <form id="form" class="validationForm" method="post" action="confirm.php" novalidate>
    <div class="form-group">
      <label for="name">お名前（必須） 
        <span class="error-php"><?php echo h( $error_name ); ?></span>
      </label>
      <input type="text" class="required maxlength form-control" data-maxlength="30" id="name" name="name" placeholder="氏名" data-error-required="お名前は必須です。" value="<?php echo h($name); ?>">
    </div>
    <div class="form-group">
      <label for="email">Email（必須） 
        <span class="error-php"><?php echo h( $error_email ); ?></span>
      </label>
      <input type="email" class="required pattern form-control" data-pattern="email" id="email" name="email" placeholder="Email アドレス" data-error-required="Email アドレスは必須です。"  data-error-pattern="Email の形式が正しくないようですのでご確認ください" value="<?php echo h($email); ?>">
    </div>
    <div class="form-group">
      <label for="email_check">Email（確認用 必須） 
        <span class="error-php"><?php echo h( $error_email_check ); ?></span>
      </label>
      <input type="email" class="form-control equal-to required" data-equal-to="email" data-error-equal-to="メールアドレスが異なります" id="email_check" name="email_check" placeholder="Email アドレス（確認用 必須）" value="<?php echo h($email_check); ?>">
    </div>
    <div class="form-group">
      <label for="tel">お電話番号（半角英数字） 
        <span class="error-php"><?php echo h( $error_tel ); ?></span>
      </label>
      <input type="tel" class="pattern form-control" data-pattern="tel" id="tel" name="tel" placeholder="お電話番号" data-error-pattern="電話番号の形式が正しくないようですのでご確認ください"  value="<?php echo h($tel); ?>">
    </div>
    <div class="form-group">
      <label for="subject">件名（必須） 
        <span class="error-php"><?php echo h( $error_subject ); ?></span> 
      </label>
      <input type="text" class="required maxlength form-control" data-maxlength="100" id="subject" name="subject" placeholder="件名" value="<?php echo h($subject); ?>">
    </div>
    <div class="form-group">
      <label for="body">お問い合わせ内容（必須） 
        <span class="error-php"><?php echo h( $error_body ); ?></span>
      </label>
      <textarea class="required maxlength showCount form-control" data-maxlength="1000" id="body" name="body" placeholder="お問い合わせ内容（1000文字まで）をお書きください" rows="3"><?php echo h($body); ?></textarea>
    </div>
    <!--確認ページへトークンをPOSTする、隠しフィールド「ticket」-->
    <input type="hidden" name="ticket" value="<?php echo h($ticket); ?>">
    <button name="submitted" type="submit" class="btn btn-primary">確認画面へ</button>
  </form>
</div>
<!-- 検証用 JavaScript の読み込み -->
<script src="formValidation.js"></script> 
</body>
</html>