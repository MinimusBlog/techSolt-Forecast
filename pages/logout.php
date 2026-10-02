<?php
// Начинаем сессию, чтобы иметь доступ к $_SESSION
session_start();

// Очищаем все переменные сессии
// Это безопасно, даже если сессия еще не была начата или пуста
$_SESSION = array();

// Если вы хотите уничтожить и куки сессии,
// удалите куку с именем сессии.
// Примечание: Это уничтожит сессию, а не данные сессии!
// if (ini_get("session.use_cookies")) {
//     $params = session_get_cookie_params();
//     setcookie(session_name(), '', time() - 42000,
//         $params["path"], $params["domain"],
//         $params["secure"], $params["httponly"]
//     );
// }

// Уничтожаем сессию на сервере
session_destroy();

// Перенаправляем пользователя на страницу входа
// Убедитесь, что путь к login.html правильный
header("Location: index.html");

// Останавливаем выполнение скрипта после перенаправления
exit;
?>