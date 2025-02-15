<?php

session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$name = (string) $data['name'] ?? null;
$email = (string) $data['email'] ?? null;
$phone = (string) $data['phone'] ?? null;

if (empty($name)) {
  echo json_encode(['success' => false, 'message' => 'Неправильно указано имя'], JSON_UNESCAPED_UNICODE);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'message' => 'Неправильно указан email'], JSON_UNESCAPED_UNICODE);
  exit;
}

if (!preg_match('/\+\d \(\d{3}\) \d{3}-\d{2}-\d{2}/', $phone)) {
  echo json_encode(['success' => false, 'message' => 'Неправильно указан телефон'], JSON_UNESCAPED_UNICODE);
  exit;
}

function getPDO(): PDO
{
  try {
    return new \PDO(dsn: 'mysql:host=' . db_host . '; charset=utf8; dbname=' . db_name, username: db_username, password: db_password);
  } catch (\PDOException $e) {
    die("Connection error: {$e->getMessage()}");
  }
}


try {
  $pdo = getPDO();

  $result = $pdo->prepare(query: "SELECT * FROM calls WHERE name = '$name' AND email = '$email' AND phone = '$phone' AND last_request >= NOW() - INTERVAL 5 MINUTE");
  $result->execute();
  $requests = array();

  while ($request = $result->fetch(PDO::FETCH_ASSOC)) {
    $requests[] = $request;
  }

  if (count($requests) > 0) {
    // http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Заявка отправлена менее 5 минут назад.'], JSON_UNESCAPED_UNICODE);
    exit();
  } else {
    $result = $pdo->prepare(query: "INSERT INTO calls (name, email, phone) VALUES ('$name', '$email', '$phone')");
    $result->execute();
    // http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Наши специалисты скоро свяжутся с Вами'], JSON_UNESCAPED_UNICODE);
    exit();
  }

} catch (Exception $err) {
  // http_response_code(404);
  echo json_encode(['success' => false, 'message' => 'Ошибка запроса', 'exc' => $err->getMessage()], JSON_UNESCAPED_UNICODE);
  exit();
}

?>
