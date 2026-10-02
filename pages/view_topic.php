<?php
// view_topic.php - Просмотр конкретной темы и сообщений
require_once('../includes/config.php');
session_start();

$topic = null;
$posts = [];
$error = ''; // Для сообщений об ошибках на этой странице

// Получаем ID темы из URL и валидируем его
$topic_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); // Безопасное получение integer

if (!$topic_id) {
    // Если ID темы некорректен или отсутствует
    $error = "Тема не найдена. Некорректный ID.";
    // header('Location: forum.php'); // Можно перенаправить на список тем
    // exit;
} else {
    // --- PHP логика для обработки добавления ответа (если форма отправлена) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Проверяем, авторизован ли пользователь для добавления ответа
        if (!isset($_SESSION['user_id'])) {
            $error = 'Для добавления ответа, пожалуйста, авторизуйтесь.';
            // Возможно, стоит перенаправить на страницу входа с параметром return_url
            // header('Location: pages/login.html?return_url=' . urlencode($_SERVER['REQUEST_URI']));
            // exit;
        } else {
            $content = trim($_POST['content'] ?? ''); // Получаем текст ответа
            $user_id = $_SESSION['user_id'];

            if (empty($content)) {
                $error = 'Пожалуйста, введите текст ответа.';
            } else {
                // Вставляем новый пост (ответ) в БД
                $stmt_post = $conn->prepare("INSERT INTO posts (topic_id, user_id, content) VALUES (?, ?, ?)");
                if ($stmt_post === false) {
                    $error = "Ошибка подготовки запроса на добавление ответа: " . htmlspecialchars($conn->error);
                } else {
                    $stmt_post->bind_param("iis", $topic_id, $user_id, $content);
                    if ($stmt_post->execute()) {
                        // Успешно добавлено, перенаправляем на эту же страницу
                        header("Location: view_topic.php?id=" . $topic_id);
                        exit;
                    } else {
                        $error = "Ошибка при добавлении ответа: " . htmlspecialchars($stmt_post->error); // Не показывать детальные ошибки БД на продакшене!
                    }
                    $stmt_post->close();
                }
            }
        }
    }


    // --- PHP логика для получения данных темы и сообщений ---
    // Получаем данные темы
    $stmt_topic = $conn->prepare("SELECT t.id, t.title, t.created_at, u.username FROM topics t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    if ($stmt_topic === false) {
        $error = "Ошибка подготовки запроса темы: " . htmlspecialchars($conn->error);
    } else {
        $stmt_topic->bind_param("i", $topic_id);
        $stmt_topic->execute();
        $result_topic = $stmt_topic->get_result();

        if ($result_topic->num_rows > 0) {
            $topic = $result_topic->fetch_assoc();

            // Получаем все посты (сообщения) для этой темы
            $stmt_posts = $conn->prepare("SELECT p.id, p.content, p.created_at, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.topic_id = ? ORDER BY p.created_at ASC");
             if ($stmt_posts === false) {
                 $error = "Ошибка подготовки запроса сообщений: " . htmlspecialchars($conn->error);
             } else {
                $stmt_posts->bind_param("i", $topic_id);
                $stmt_posts->execute();
                $result_posts = $stmt_posts->get_result();

                if ($result_posts->num_rows > 0) {
                    while($row = $result_posts->fetch_assoc()) {
                        $posts[] = $row;
                    }
                }
                $stmt_posts->close();
             }

        } else {
            $error = "Тема с таким ID не найдена.";
            // header('Location: forum.php'); // Можно перенаправить на список тем
            // exit;
        }
        $stmt_topic->close();
    }
}


$conn->close(); // Закрываем соединение с БД
?>

<!DOCTYPE html>
<html lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $topic ? htmlspecialchars($topic['title']) : 'Тема не найдена'; ?></title>
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
            <a class="header__login" href="register.html" aria-label="Вход в личный кабинет">
                <img src="../images/icons/user.svg" alt="Иконка пользователя" />
            </a>
            <button class="header__mobile-menu-button" aria-expanded="false" aria-haspopup="true">
                <img src="../images/icons/burger.svg" alt="Мобильное меню" />
            </button>
        </div>
    </header>
    <div class="topic-container">
        <p><a href="forum.php" class="topic__back-link">Вернуться к списку тем</a></p>

        <?php if ($error): // Выводим ошибку, если тема не найдена или ошибка БД/валидации ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif ($topic): // Если тема успешно загружена ?>
            <h2 class="forum-text "><?php echo htmlspecialchars($topic['title']); ?></h2>

            <?php if (!empty($posts)): ?>
                <div class="posts-list">
                    <?php foreach ($posts as $index => $post): ?>
                        <div class="post <?php echo $index === 0 ? 'post--topic' : 'post--reply'; ?>">
                            <div class="post__meta">
                                Автор: <?php echo htmlspecialchars($post['username']); ?>
                                | Дата: <?php echo htmlspecialchars($post['created_at']); ?>
                            </div>
                            <div class="post__content forum-text__label">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?> </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>В этой теме пока нет сообщений.</p>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): // Показываем форму ответа только авторизованным ?>
                 <div class="reply-form">
                     <h3 class="forum-text__label">Добавить ответ</h3>
                     <form action="view_topic.php?id=<?php echo htmlspecialchars($topic['id']); ?>" method="post">
                         <div class="form-group">
                             <label for="reply_content" class="forum-text__label">Ваш ответ:</label>
                             <textarea id="reply_content" name="content" rows="5" required></textarea>
                         </div>
                         <button type="submit">Отправить ответ</button>
                     </form>
                 </div>
            <?php else: ?>
                 <p>Для добавления ответов, пожалуйста, <a href="pages/login.html">авторизуйтесь</a>.</p>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</body>
</html>