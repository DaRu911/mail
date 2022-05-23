<?php
//セッションを開始
session_start(); 
 
//エスケープ処理やデータをチェックする関数を記述したファイルの読み込み
require 'libs/functions.php'; 
 
//お問い合わせ日時を日本時間に
date_default_timezone_set('Asia/Tokyo'); 
 
//POSTされたデータをチェック
$_POST = checkInput( $_POST );
//固定トークンを確認（CSRF対策）
if ( isset( $_POST[ 'ticket' ], $_SESSION[ 'ticket' ] ) ) {
  $ticket = $_POST[ 'ticket' ];
  if ( $ticket !== $_SESSION[ 'ticket' ] ) {
    //トークンが一致しない場合は処理を中止
    die( 'Access denied' );
  }
} else {
  //トークンが存在しない場合（入力ページにリダイレクト）
  //die( 'Access Denied（直接このページにはアクセスできません）' );  //処理を中止する場合
  $dirname = dirname( $_SERVER[ 'SCRIPT_NAME' ] );
  $dirname = $dirname == DIRECTORY_SEPARATOR ? '' : $dirname;
  //サーバー変数 $_SERVER['HTTPS'] が取得出来ない環境用
  if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and $_SERVER['HTTP_X_FORWARDED_PROTO'] === "https") {
    $_SERVER[ 'HTTPS' ] = 'on';
  }
  //入力画面（contact.php）の URL
  $url = ( empty( $_SERVER[ 'HTTPS' ] ) ? 'http://' : 'https://' ) . $_SERVER[ 'SERVER_NAME' ] . $dirname . '/contact.php';
  header( 'HTTP/1.1 303 See Other' );
  header( 'location: ' . $url );
  exit;
}
 
//変数にエスケープ処理したセッション変数の値を代入
$name = h( $_SESSION[ 'name' ] );
$email = h( $_SESSION[ 'email' ] ) ;
$tel =  h( $_SESSION[ 'tel' ] ) ;
$subject = h( $_SESSION[ 'subject' ] );
$body = h( $_SESSION[ 'body' ] );
 
//メール本文の組み立て
$mail_body = 'コンタクトページからのお問い合わせ' . "\n\n";
$mail_body .=  date("Y年m月d日 H時i分") . "\n\n"; 
$mail_body .=  "お名前： " .$name . "\n";
$mail_body .=  "Email： " . $email . "\n"  ;
$mail_body .=  "お電話番号： " . $tel . "\n\n" ;
$mail_body .=  "＜お問い合わせ内容＞" . "\n" . $body;
  
//-------- ★★★ PHPMailer を使ったメールの送信処理（Gmailサーバ） ★★★ ------------
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth; //  ### 追加 ### 
 
// Alias the League Google OAuth2 provider class
use League\OAuth2\Client\Provider\Google;//  ### 追加 ### 
 
//PHPMailer の読み込み（PHPMailer の位置により適宜変更）
require 'vendor/autoload.php';
//Gmail の SMTP 認証情報（クライアント ID等）の読み込み
require 'libs/phpmailvars_oauth2.php';
 
//mbstring の日本語設定
mb_language( "japanese" );
mb_internal_encoding( "UTF-8" );
 
// ###  OAUTH2 設定に使う値を変数に代入（値は phpmailvars_oauth2.php）   ### 
//Gmail メールアドレス
$google_email = GMAIL_ADDRESS;
//クライアント ID
$clientId = CLIENT_ID;
//クライアントシークレット
$clientSecret = CLIENT_SECRET;
//トークン（Refresh Token）
$refreshToken = REFRESH_TOKEN;
 
//PHPMailer のインスタンスを生成 
$mail = new PHPMailer( true );
 
// ###  OAUTH2 の設定  ### 
//OAuth2 プロバイダのインスタンスの生成 
$provider = new Google(
  [
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
  ]
);
//送信結果の真偽値の初期化
$result = false;
$result2 = false;
 
try {
  //サーバ設定
  //$mail->SMTPDebug = SMTP::DEBUG_SERVER; // デバグの出力を有効に
  $mail->isSMTP(); // SMTP を使用
  // SMTP サーバーを指定
  $mail->Host = MAIL_HOST; 
  // SMTP authentication を有効に
  $mail->SMTPAuth = true; 
  //AuthType を XOAUTH2 に指定
  $mail->AuthType = 'XOAUTH2'; 
  // 暗号化(TLS)を有効に
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  //ポートの指定
  $mail->Port = 587;
 
  //日本語用
  $mail->CharSet = "iso-2022-jp";
  $mail->Encoding = "7bit";
  
  // ###  OAUTH2 の設定  ### 
  //OAuth プロバイダのインスタンスを PHPMailer へ渡す
  $mail->setOAuth(
    new OAuth(
      [
        'provider' => $provider,
        'clientId' => $clientId,
        'clientSecret' => $clientSecret,
        'refreshToken' => $refreshToken,
        'userName' => $google_email,
      ]
    )
  );
 
  //受信者設定
  //差出人アドレス, 差出人名（差出人アドレスには Gmail アカウントのアドレスを指定）
  $mail->setFrom($google_email, mb_encode_mimeheader(GMAIL_ACCT_NAME));  
  //送信先アドレス (この例の場合は Gmail のアドレス)・宛先名
  $mail->AddAddress($google_email, mb_encode_mimeheader(GMAIL_ACCT_NAME)); 
  //返信アドレスに差出人（お問い合わせをしたユーザ）を指定
  $mail->addReplyTo($email, mb_encode_mimeheader($name)); 
  //Bcc アドレス
  $mail->AddBcc( BCC );  
  // テキスト形式メール
  $mail->isHTML( false );
  //件名
  $mail->Subject = mb_encode_mimeheader( $subject ); 
  //70 文字で改行（好みで）
  $mail->WordWrap = 70; 
  //本文
  $mail->Body = mb_convert_encoding( $mail_body, "JIS", "UTF-8" );
 
  //メール送信の結果（真偽値）を $result に代入
  $result = $mail->send();
 
} catch ( Exception $e ) {
  $result = false;
  //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
 
//メール送信の結果($result) を判定
if ( $result ) {
  
  //成功した場合はセッションを破棄
  $_SESSION = array(); //空の配列を代入し、すべてのセッション変数を消去 
  session_destroy(); //セッションを破棄
 
  //自動返信メール
  $autoresponder = new PHPMailer( true );
  try {
    //サーバ設定
    //$autoresponder->SMTPDebug = SMTP::DEBUG_SERVER; // デバグの出力を有効に
    // SMTP を使用
    $autoresponder->isSMTP(); 
    // Gmail SMTP サーバーを指定
    $autoresponder->Host = MAIL_HOST; 
    // SMTP authentication を有効に
    $autoresponder->SMTPAuth = true; 
    //AuthType を XOAUTH2 に指定
    $autoresponder->AuthType = 'XOAUTH2';
    $autoresponder->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $autoresponder->Port = 587;
    
    // ###  OAUTH2 の設定  ### 
    //OAuth プロバイダのインスタンスを PHPMailer へ渡す
    $autoresponder->setOAuth(
      new OAuth(
        [
          'provider' => $provider,
          'clientId' => $clientId,
          'clientSecret' => $clientSecret,
          'refreshToken' => $refreshToken,
          'userName' => $google_email,
        ]
      )
    );
 
    //日本語用
    $autoresponder->CharSet = "iso-2022-jp";
    $autoresponder->Encoding = "7bit";
 
    //受信者設定
    //差出人アドレス, 差出人名（差出人アドレスには Gmail アカウントのアドレスを指定 ）
    $autoresponder->setFrom($google_email, mb_encode_mimeheader(GMAIL_ACCT_NAME)); 
    //送信先・宛先名（お問い合わせしたユーザ）
    $autoresponder->AddAddress( $email, mb_encode_mimeheader( $name ) ); 
    // テキスト形式メール
    $autoresponder->isHTML( false );
    //件名
    $autoresponder->Subject = mb_encode_mimeheader( "自動返信メール" ); 
    //返信用アドレス
    $autoresponder->addReplyTo( $google_email, mb_encode_mimeheader("お問い合わせ")); 
    $autoresponder->WordWrap = 70; //70 文字で改行（好みで）
    $ar_body = $name." 様\n\n";
    $ar_body .= "この度は、お問い合わせ頂き誠にありがとうございます。" . "\n\n";
    $ar_body .= "下記の内容でお問い合わせを受け付けました。\n\n";
    $ar_body .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
    $ar_body .= "お名前：" . $name . "\n";
    $ar_body .= "メールアドレス：" . $email . "\n";
    $ar_body .= "お電話番号： " . $tel . "\n\n" ;
    $ar_body .="＜お問い合わせ内容＞" . "\n" . $body;
    $autoresponder->Body = mb_convert_encoding( $ar_body, "JIS", "UTF-8" );
    //自動送信メールの送信結果（真偽値）を result2 に代入
    $result2 = $autoresponder->send();
  } catch ( Exception $e ) {
    echo "Auto Response Message could not be sent. Mailer Error: {$autoresponder->ErrorInfo}";
  }
} else {
  //送信失敗時（もしあれば）
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>コンタクトフォーム（完了）</title>
<link href="../bootstrap.min.css" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
</head>
<body>
<div class="container">
  <h2>お問い合わせフォーム</h2>
  <?php if ( $result ): ?>
  <h3>送信完了!</h3>
  <p>お問い合わせいただきありがとうございます。</p>
  <p>送信完了いたしました。</p>
    <?php if ( $result2 ): ?>
    <p>確認の自動返信メールを <?php echo $email; ?> へお送りいたしました。</p>
    <?php else: ?>
    <p>確認の自動返信メールを送信できませんでした。</p>
    <?php endif; ?>
  <?php else: ?>
  <p>申し訳ございませんが、送信に失敗しました。</p>
  <p>しばらくしてもう一度お試しになるか、メールにてご連絡ください。</p>
  <p>ご迷惑をおかけして誠に申し訳ございません。</p>
  <?php endif; ?>
</div>
</body>
</html>