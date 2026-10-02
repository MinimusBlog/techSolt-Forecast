<?php
require_once('../includes/config.php');
session_start(); // для проверки авторизации

// получение тем
$topics = [];
$sql = "SELECT t.id, t.title, t.created_at, u.username
        FROM topics t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC"; // Получаем темы и сортируем по новизне

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $topics[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форум</title>
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
            <a class="header__login" href="../pages/register.html" aria-label="Вход в личный кабинет">
                <img src="../images/icons/user.svg" alt="Иконка пользователя" />
            </a>
            <button class="header__mobile-menu-button" aria-expanded="false" aria-haspopup="true">
                <img src="../images/icons/burger.svg" alt="Мобильное меню" />
            </button>
        </div>
    </header>

    <div class="forum-container">
        <div forum__left>
        <h2 class="forum-text">Форум</h2>
        </div>

        <?php if (isset($_SESSION['user_id'])): // Показываем кнопку "Создать тему" только авторизованным ?>
            <a href="create_topic.php" class="forum__create-link">Создать новую тему</a>
        <?php else: ?>
            <p>Для создания тем, пожалуйста, <a href="login.html">авторизуйтесь</a>.</p>
        <?php endif; ?>

        <?php if (!empty($topics)): ?>
            <ul class="topic-list">
                <?php foreach ($topics as $topic): ?>
                    <li class="topic-list__item">
                        <a href="view_topic.php?id=<?php echo htmlspecialchars($topic['id']); ?>" class="topic-list__link">
                            <?php echo htmlspecialchars($topic['title']); ?>
                        </a>
                        <p class="topic-list__meta">
                            Автор: <?php echo htmlspecialchars($topic['username']); ?>
                            | Создано: <?php echo htmlspecialchars($topic['created_at']); ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Тем на форуме пока нет. Будьте первым!</p>
        <?php endif; ?>
    </div>
</body>
</html>