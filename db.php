<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('ignore_repeated_errors', 0);
try {
    $pdo = new PDO('mysql:host=localhost;dbname=test_db', 'root', 'root');
} catch (PDOException $e) {
    echo $e->getMessage();
    die;
}
$email = $_POST['email'];
if (!$email) {
    return;
}
$ret = $pdo->query("SELECT id FROM first_table WHERE `email`= '$email'");
$user = $ret->fetchAll(PDO::FETCH_ASSOC);
if (!empty($user)) {
    $user_id = $user[0]['id'];
} else {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    //подготавливаем запрос на запись
    $query = $pdo->prepare(
        "INSERT INTO first_table(email, name, phone) VALUE ('$email', '$name', '$phone')"
    );
    //выполняем запрос на запись
    $query->execute();
    //достаем id последней записи
    $user_id = $pdo->lastInsertId();
}
//получаем данные по заказу
$street = 'ул. ' . $_POST['street'];
$home = 'д. ' . $_POST['home'];
$part = $_POST['part'];
$appt = 'кв. ' . $_POST['appt'];
$floor = $_POST['floor'] . ' этаж';
$comment = $_POST['comment'];
//$payment = $_POST['payment'];
//$callback = $_POST['callback'];

$query = $pdo->prepare("INSERT INTO adress(`street`, `building`, `build`, `flat`, `floor`, `comment`,`user_id`)
 VALUES ('$street', '$home', '$part','$appt', '$floor', '$comment', $user_id)");
$query->execute();
$orderId = $pdo->lastInsertId();

//TODO добавить переносы \n в message
$message = "Заголовок - заказ №{$orderId}\n";

//собираем адрес
$address = implode(', ', [$street, $home, $part, $appt, $floor]);

$message .= 'Ваш заказ будет доставлен по адресу: ' . $address.'\n';
$message .= 'Содержимое заказа - DarkBeefBurger, 500 рублей, 1 шт,\n ';

$orderCount = 0;
$query = $pdo->query("SELECT COUNT(*) as total FROM adress WHERE user_id = $user_id");
$orderCount = $query->fetchAll(PDO::FETCH_ASSOC)[0]['total'];
if ($orderCount > 1) {
    $message .= 'Спасибо! Это ваш ' . $orderCount . ' заказ!\n';
} elseif ($orderCount === 1) {
    $message .= 'Спасибо! Это ваш первый заказ!\n';
}

file_put_contents("/order_$orderId.txt", $message);
