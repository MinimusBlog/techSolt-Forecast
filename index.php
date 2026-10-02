<?php
require_once ('includes/config.php'); 

// Переменная для хранения текста решения
$solutionText = null;
$errorMessage = null; // Для сообщений об ошибках или невыбранных ответах
$solutionId = null; // Переменная для хранения ID найденного решения

// Проверяем, была ли форма отправлена методом POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --------- 1. Получаем и валидируем ответы (Используем простые ключи q1...q12) ---------
    // Убедитесь, что в HTML у select-ов стоят name="q1", name="q2" и т.д.
    $answers = [
        'q1' => $_POST['q1'] ?? null,
        'q2' => $_POST['q2'] ?? null,
        'q3' => $_POST['q3'] ?? null,
        'q4' => $_POST['q4'] ?? null,
        'q5' => $_POST['q5'] ?? null,
        'q6' => $_POST['q6'] ?? null,
        'q7' => $_POST['q7'] ?? null,
        'q8' => $_POST['q8'] ?? null,
        'q9' => $_POST['q9'] ?? null,
        'q10' => $_POST['q10'] ?? null,
        'q11' => $_POST['q11'] ?? null,
        'q12' => $_POST['q12'] ?? null,
    ];

    // Проверяем, что все обязательные поля выбраны (value не пустое)
    $allAnswered = true;
    foreach ($answers as $key => $answer) {
        if ($answer === null || $answer === '') {
            $allAnswered = false;
            $errorMessage = "Пожалуйста, ответьте на все вопросы чек-листа.";
            break;
        }
    }

    if ($allAnswered) {
        // --------- 2. Определяем ID решения на основе ответов (логика диагностики) ---------
        // Эта логика остается такой же, как в предыдущем примере, просто использует ключи q1...q12

        if ($answers['q1'] === 'Нет') {
            $solutionId = 1; // Проверьте питание
        } elseif ($answers['q1'] === 'Да') {
            if ($answers['q2'] === 'Нет') {
                if ($answers['q8'] === 'Да') {
                    $solutionId = 5; // Анализируйте бипы/коды
                } elseif ($answers['q10'] === 'Нет') {
                     $solutionId = 6; // Проверьте/переустановите ОЗУ
                } elseif ($answers['q5'] === 'Нет') {
                     $solutionId = 7; // Подключите доп. питание видеокарты
                } elseif ($answers['q6'] === 'Нет') {
                     $solutionId = 8; // Проверьте установку CPU AMD
                } elseif ($answers['q9'] === 'Нет') {
                     $solutionId = 9; // Проверьте установку кулера CPU
                }
                if ($solutionId === null) { // Если нет изображения, но специфическая причина не найдена
                     $solutionId = 2; // Общая диагностика "Нет изображения"
                }

            } elseif ($answers['q2'] === 'Да') {
                if ($answers['q11'] === 'Нет') {
                    $solutionId = 4; // Проверьте подключение накопителей
                } elseif ($answers['q12'] === 'Да') {
                     $solutionId = 10; // Диагностика сетевого подключения
                } elseif ($answers['q7'] === 'Да') {
                     $solutionId = 11; // Отключите авторазгон
                }
                if ($solutionId === null) { // Если изображение есть, но специфическая проблема не найдена
                    $solutionId = 3; // Общие рекомендации
                }
            }
        }

        // Если по любой причине solutionId так и не был определен
        if ($solutionId === null) {
            $solutionId = 3; // Решение по умолчанию
        }


        // --------- 3. Выбираем текст решения из базы данных ---------
        // Этот блок остается без изменений
        $stmt = $conn->prepare("SELECT solution_text FROM solutions WHERE id = ?");
        if ($stmt === false) {
            $errorMessage = "Ошибка базы данных: " . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param("i", $solutionId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $solutionText = $row['solution_text']; // Получаем текст решения
            } else {
                $errorMessage = "Ошибка: Решение с ID " . htmlspecialchars($solutionId) . " не найдено в базе данных.";
            }
            $stmt->close();
        }
    }
}

// Закрываем соединение с базой данных, если оно было открыто
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./styles/global.css">
    <link rel="stylesheet" href="./styles/header.css">
    <link rel="stylesheet" href="./styles/components.css">
    <link rel="stylesheet" href="./styles/page.css">
    <title>TechSolt Forecast</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,200,300,regular,500,600,700,800,900,100italic,200italic,300italic,italic,
        500italic,600italic,700italic,800italic,900italic" rel="stylesheet">
    
</head>
<body>
    <header class="header">
        <div class="header__wrapper">
            <a href="#"><img class="header__logo" src="./images/logos/logo.svg" alt="Логотип" /></a>
            <nav>
                <ul class="menu">
                    <li class="menu__item menu__item_active"><a href="#">Чек-лист</a></li>
                    <li class="menu__item"><a href="#">О нас</a></li>
                    <li class="menu__item"><a href="./pages/forum.php">Форум</a></li>
                    <li class="menu__item"><a href="#">База знаний</a></li>
                </ul>
            </nav>
            <a class="header__login" href="./pages/register.html" aria-label="Вход в личный кабинет">
                <img src="./images/icons/user.svg" alt="Иконка пользователя" />
            </a>
            <button class="header__mobile-menu-button" aria-expanded="false" aria-haspopup="true">
                <img src="./images/icons/burger.svg" alt="Мобильное меню" />
            </button>
        </div>
    </header>
    <section class="section section_light">
        <div class="hero">
            <div class="hero__left">
                <h1 class="hero__h1">Интерактивный чек-лист<br />
                    <span class="hero__h1_gradient">Диагностики и ремонта</span>
                </h1>
                <div class="hero__cta">
                    <button class="button button_primary">Пройти чек-лист</button>
                    <a href="./pages/forum.php"><button class="button button_ghost">Форум</button></a>
                </div>
            </div>
            <img class="hero__right" src="./images/video.png" alt="Видео о чек-листе"/>
        </div>
    </section>
    <section class="section">
        <div class="section__wrapper">
            <div class="check__left">
                <h1 class="hero__h1">Чек-лист</h1>
                <div class="question_wrapper">
                    <form action="" method="post" class="checklist-form">
                        <div class="checklist_wrapper">
                            <label class="question" for="q1">1. Компьютер включается?</label>
                            <select class="checklist" name="q1" id="q1" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q2">2. Есть изображение на экране?</label>
                            <select class="checklist" name="q2" id="q2" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q3">3. Подключены ли все кабели?</label>
                            <select class="checklist" name="q3" id="q3" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q4">4. Есть ли индикация на материнской плате?</label>
                            <select class="checklist" name="q4" id="q4" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q5">5. Подключены ли дополнительные пины видеокарты?</label>
                            <select class="checklist" name="q5" id="q5" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q6">6. Если процессор AMD не выпал ли он из сокета?</label>
                            <select class="checklist" name="q6" id="q6" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q7">7. Если процессор Intel 13-14 покление включен ли авторазгон?</label>
                            <select class="checklist" name="q7" id="q7" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q8">8. Издает ли компьютер звуковые сигналы (бипы) или показывает коды ошибок?</label>
                            <select class="checklist" name="q8" id="q8" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q9">9. Правильно ли установлен радиатор в материнской плате?</label>
                            <select class="checklist" name="q9" id="q9" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q10">10. Правильно ли установлен модуль памяти?</label>
                            <select class="checklist" name="q10" id="q10" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q11">11. Подключены ли накопители?</label>
                            <select class="checklist" name="q11" id="q11" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <label class="question" for="q12">12. Имеются ли проблемы с сетевым подключением?</label>
                            <select class="checklist" name="q12" id="q12" required>
                                <option value="Выберите ответ" disabled selected>Выберите ответ</option>
                                <option value="Да">Да</option>
                                <option value="Нет">Нет</option>
                            </select>
                        </div>
                        <div class="checklist_wrapper">
                            <button type="submit" class="button button_primary">Получить решение</button>
                        </div>
                    </form>
                </div>
                <div class="solution_wrapper">
                    <div class="solution__left">
                        <h2 class="solution__h2">Решение:</h2>
                        <div class="solution-output">
                        <?php
                                if ($errorMessage) {
                                    echo '<p class="solution-output__error">' . htmlspecialchars($errorMessage) . '</p>';
                                } elseif ($solutionText) {
                                    echo '<p class="solution-output__text">' . nl2br(htmlspecialchars($solutionText)) . '</p>'; // nl2br для сохранения переносов строк из БД
                                } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
                                     // Если форма отправлена, но решения не найдено
                                        if ($solutionId === null && !$errorMessage) {
                                            echo '<p class="solution-output__info">Не удалось найти специфическое решение по вашим ответам. Попробуйте обратиться на форум или к специалисту!</p>';
                                    }
                                }
                            ?>
                        </div>
                        <div class="solution__cta">
                            <button class="button button_primary" onclick="/pages/forum.php">Обратится на форум</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>