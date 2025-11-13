// Инициализация диаграммы после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, что объект настроек существует
    if (typeof githubCommitChartSettings === 'undefined') {
        console.error('GitHub Commit Chart: Settings not found');
        return;
    }

    // Проверяем обязательные поля в настройках
    if (!githubCommitChartSettings.ajaxUrl) {
        console.error('GitHub Commit Chart: ajaxUrl not found in settings');
        return;
    }

    if (!githubCommitChartSettings.nonce) {
        console.error('GitHub Commit Chart: nonce not found in settings');
        return;
    }

    // Функция для создания диаграммы коммитов
    function createCommitChart(container, githubProfile) {
        // Проверяем, что указан профиль GitHub
        if (!githubProfile) {
            container.innerHTML = '<div class="github-commit-chart-error">Ошибка: Не указан профиль GitHub</div>';
            return;
        }

        // Отладочный вывод (убрать в production)
        // console.log('GitHub Commit Chart: Creating chart for', githubProfile);

        // Показываем индикатор загрузки
        container.innerHTML = '<div class="github-commit-chart-loading">Загрузка диаграммы коммитов...</div>';

        // Отправляем AJAX запрос для получения данных о коммитах
        var xhr = new XMLHttpRequest();
        var params = 'action=gcc_get_commit_data&github_profile=' + encodeURIComponent(githubProfile) + '&nonce=' + encodeURIComponent(githubCommitChartSettings.nonce);


        // Настраиваем и отправляем AJAX запрос
        xhr.open('POST', githubCommitChartSettings.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // Обработчик ответа на запрос
        xhr.onload = function() {

            if (xhr.status === 200) {
                try {
                    // Парсим JSON ответ
                    var response = JSON.parse(xhr.responseText);

                    if (response.success) {
                        // Отображаем диаграмму с полученными данными
                        renderCommitChart(container, githubProfile, response.data);
                    } else {
                        // Отображаем ошибку, если запрос не удался
                        container.innerHTML = '<div class="github-commit-chart-error">Ошибка: ' + (response.data || 'Неизвестная ошибка') + '</div>';
                    }
                } catch (e) {
                    // Отображаем ошибку при неудачном парсинге JSON
                    container.innerHTML = '<div class="github-commit-chart-error">Ошибка парсинга данных</div>';
                }
            } else {
                // Отображаем ошибку при неудачном HTTP запросе
                container.innerHTML = '<div class="github-commit-chart-error">Ошибка загрузки данных: ' + xhr.status + '</div>';
            }
        };

        // Обработчик сетевой ошибки
        xhr.onerror = function() {
            container.innerHTML = '<div class="github-commit-chart-error">Ошибка сети</div>';
        };

        // Отправляем запрос
        xhr.send(params);
    }

    // Функция для отображения диаграммы коммитов
    function renderCommitChart(container, githubProfile, commitData) {
        // Создаем элементы диаграммы
        var chartHTML = '<div class="github-commit-chart">';
        chartHTML += '<h3>Диаграмма коммитов для ' + githubProfile + '</h3>';
        chartHTML += '<div class="chart-container">';
        chartHTML += '</div>';

        container.innerHTML = chartHTML;

        // Создаем визуализацию данных в виде тепловой карты
        renderCommitChartHeatmap(container, commitData);
    }

    // Функция для отображения диаграммы в виде тепловой карты
    function renderCommitChartHeatmap(container, commitData) {
        // Получаем контейнер для диаграммы
        var chartContainer = container.querySelector('.chart-container');
        if (!chartContainer) {
            return;
        }

        // Проверяем, что данные существуют
        if (!commitData || typeof commitData !== 'object') {
            chartContainer.innerHTML = '<div class="github-commit-chart-error">Нет данных для отображения</div>';
            return;
        }


        // Преобразуем данные в массив и сортируем по дате
        var dates = Object.keys(commitData).sort();
        var maxCommits = 0;

        // Находим максимальное количество коммитов в день для расчета интенсивности цвета
        for (var date in commitData) {
            if (commitData.hasOwnProperty(date) && commitData[date] > maxCommits) {
                maxCommits = commitData[date];
            }
        }

        // Создаем тепловую карту
        var html = '<div class="commit-heatmap horizontal-heatmap">';

        // Создаем массив месяцев для отображения
        var months = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];

        // Определяем начальную и конечную даты (последние 52 недели)
        var endDate = new Date();
        var startDate = new Date();
        startDate.setDate(endDate.getDate() - 365); // Примерно 52 недели

        // Создаем заголовки месяцев
        html += '<div class="heatmap-row month-labels">';
        html += '<div class="heatmap-label"></div>'; // Пустая ячейка для выравнивания

        // Создаем отображение месяцев (52 ячейки для недель)
        var currentDate = new Date(startDate);
        var monthStarts = []; // Храним позиции начала месяцев

        // Определяем позиции начала месяцев
        for (var week = 0; week < 52; week++) {
            var monday = new Date(currentDate);
            var dayOfWeek = monday.getDay();
            if (dayOfWeek === 0) dayOfWeek = 7; // Воскресенье = 7
            monday.setDate(monday.getDate() - (dayOfWeek - 1)); // Устанавливаем на понедельник

            // Проверяем, является ли этот понедельник началом месяца
            if (monday.getDate() <= 7) { // Если 1-е число находится в первой неделе месяца
                var monthIndex = monday.getMonth();
                if (monthStarts.length === 0 || monthStarts[monthStarts.length - 1].month !== monthIndex) {
                    monthStarts.push({
                        week: week,
                        month: monthIndex,
                        name: months[monthIndex]
                    });
                }
            }

            currentDate.setDate(currentDate.getDate() + 7); // Переходим к следующей неделе
        }

        // Создаем ячейки для месяцев
        var lastPosition = 0;
        for (var m = 0; m < monthStarts.length; m++) {
            var monthInfo = monthStarts[m];
            var position = monthInfo.week;

            // Добавляем пустые ячейки перед месяцем
            for (var i = lastPosition; i < position; i++) {
                html += '<div class="heatmap-label"></div>';
            }

            // Добавляем ячейку для месяца
            html += '<div class="heatmap-label month-name">' + monthInfo.name + '</div>';

            lastPosition = position + 1;
        }

        // Заполняем оставшиеся ячейки
        for (var i = lastPosition; i < 52; i++) {
            html += '<div class="heatmap-label"></div>';
        }

        html += '</div>';

        // Создаем строки для каждой недели последних 52 недель
        var currentDate = new Date();
        currentDate.setDate(currentDate.getDate() - 365); // Начинаем с 52 недель назад

        // Создаем 7 строк для дней недели (Пн-Вс)
        var daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        for (var dayIndex = 0; dayIndex < 7; dayIndex++) {
            html += '<div class="heatmap-row">';

            // Добавляем метку дня недели
            html += '<div class="heatmap-label">' + daysOfWeek[dayIndex] + '</div>';

            // Создаем ячейки для каждого дня последних 52 недель
            var currentWeek = new Date(currentDate);
            // Устанавливаем на понедельник той недели, в которой находится startDate
            var dayOfWeek = currentWeek.getDay();
            if (dayOfWeek === 0) dayOfWeek = 7; // Воскресенье = 7
            currentWeek.setDate(currentWeek.getDate() - (dayOfWeek - 1) + dayIndex);

            // Отображаем 52 недели
            for (var week = 0; week < 52; week++) {
                var dateStr = currentWeek.toISOString().split('T')[0];
                var commits = commitData[dateStr] || 0;

                // Определяем интенсивность цвета (от 0 до 4)
                var intensity = 0;
                if (maxCommits > 0 && commits > 0) {
                    intensity = Math.ceil((commits / maxCommits) * 4);
                    if (intensity > 4) intensity = 4;
                    if (intensity < 1) intensity = 1;
                }

                // Добавляем ячейку с подсказкой, показывающей дату и количество коммитов
                html += '<div class="heatmap-cell intensity-' + intensity + '" title="' + dateStr + ': ' + commits + ' коммитов"></div>';

                // Переходим к следующей неделе
                currentWeek.setDate(currentWeek.getDate() + 7);
            }

            html += '</div>';
        }

        html += '</div>';

        // Вставляем сгенерированный HTML в контейнер
        chartContainer.innerHTML = html;
    }

    // Находим все контейнеры диаграмм и создаем для них диаграммы
    var chartContainers = document.querySelectorAll('.github-commit-chart-container');
    chartContainers.forEach(function(container) {
        var githubProfile = container.getAttribute('data-github-profile');
        if (githubProfile) {
            createCommitChart(container, githubProfile);
        }
    });
});