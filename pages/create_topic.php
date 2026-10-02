<?php
// create_topic.php - Создание новой темы
require_once('../includes/config.php');
session_start();

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    // Если не авторизован, перенаправляем на страницу входа
    header('Location: pages/login.html'); // Укажите правильный путь к вашей странице входа
    exit;
}

$error = ''; // Переменная для сообщений об ошибках

// Обработка отправленной формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? ''); // Получаем заголовок и удаляем пробелы по краям
    $content = trim($_POST['content'] ?? ''); // Получаем текст и удаляем пробелы по краям
    $user_id = $_SESSION['user_id']; // ID текущего авторизованного пользователя

    // Валидация данных
    if (empty($title)) {
        $error = 'Пожалуйста, введите заголовок темы.';
    } elseif (empty($content)) {
        $error = 'Пожалуйста, введите текст первого сообщения.';
    } else {
        // Данные валидны, приступаем к сохранению в БД

        // Начинаем транзакцию для атомарного добавления темы и первого поста
        $conn->begin_transaction();

        try {
            // Вставляем новую тему
            $stmt_topic = $conn->prepare("INSERT INTO topics (user_id, title) VALUES (?, ?)");
            $stmt_topic->bind_param("is", $user_id, $title); // i = integer, s = string
            $stmt_topic->execute();

            $new_topic_id = $conn->insert_id; // Получаем ID только что вставленной темы
            $stmt_topic->close();

            // Вставляем первое сообщение (как пост)
            $stmt_post = $conn->prepare("INSERT INTO posts (topic_id, user_id, content) VALUES (?, ?, ?)");
            $stmt_post->bind_param("iis", $new_topic_id, $user_id, $content);
            $stmt_post->execute();

            $stmt_post->close();

            // Если обе операции успешны, завершаем транзакцию
            $conn->commit();

            // Перенаправляем пользователя на страницу просмотра созданной темы
            header("Location: view_topic.php?id=" . $new_topic_id);
            exit;

        } catch (mysqli_sql_exception $exception) {
            // В случае ошибки откатываем транзакцию
            $conn->rollback();
            $error = "Ошибка при создании темы: " . $exception->getMessage(); // Не показывать пользователю детальные ошибки БД на продакшене!
        }
    }
}

$conn->close(); // Закрываем соединение с БД (если оно еще открыто)
?>

<!DOCTYPE html>
<html lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать тему</title>
    <link rel="stylesheet" href="../styles/global.css">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/page.css">
<body>
<header class="header">
        <div class="header__wrapper">
            <a href="../index.php"><img class="header__logo" src="../images/logos/logo.svg" alt="Логотип" /></a>
            <nav>
                <ul class="menu">
                    <li class="menu__item menu__item_active"><a href="../index.php">Чек-лист</a></li>
                    <li class="menu__item"><a href="#">О нас</a></li>
                    <li class="menu__item"><a href="./pages/forum.php">Форум</a></li>
                    <li class="menu__item"><a href="#">База знаний</a></li>
                </ul>
            </nav>
            <a class="header__login" href="./register.html" aria-label="Вход в личный кабинет">
                <img src="../images/icons/user.svg" alt="Иконка пользователя" />
            </a>
            <button class="header__mobile-menu-button" aria-expanded="false" aria-haspopup="true">
                <img src="../images/icons/burger.svg" alt="Мобильное меню" />
            </button>
        </div>
    </header>
    <div class="create-topic-container">
        <h2 class="forum-text">Создать новую тему</h2>

        <?php if ($error): // Выводим сообщение об ошибке, если есть ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form action="create_topic.php" method="post" class="create-topic-form">
            <div class="form-group">
                <label class="forum-text__label" for="title">Заголовок:</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($title ?? ''); ?>"> </div>
            <div class="form-group">
                <label class="forum-text__label" for="content">Текст сообщения:</label>
                <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($content ?? ''); ?></textarea> </div>
            <button type="submit">Создать тему</button>
        </form>

        <p><a href="forum.php" class="text-hover">Вернуться к списку тем</a></p>

    </div>
</body>
</html>