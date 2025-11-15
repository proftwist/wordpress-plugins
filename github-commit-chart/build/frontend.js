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
    function createCommitChart(container, githubProfile, selectedYear = null) {
        // Проверяем, что указан профиль GitHub
        if (!githubProfile) {
            container.innerHTML = '<div class="github-commit-chart-error">Ошибка: Не указан профиль GitHub</div>';
            return;
        }

        // Если год не указан, используем текущий год
        if (selectedYear === null) {
            selectedYear = new Date().getFullYear();
        }

        // Отладочный вывод (убрать в production)
        // console.log('GitHub Commit Chart: Creating chart for', githubProfile, 'year:', selectedYear);

        // Показываем индикатор загрузки
        container.innerHTML = '<div class="github-commit-chart-loading">Загрузка диаграммы коммитов...</div>';

        // Отправляем AJAX запрос для получения данных о коммитах
        var xhr = new XMLHttpRequest();
        var params = 'action=gcc_get_commit_data&github_profile=' + encodeURIComponent(githubProfile) + '&year=' + encodeURIComponent(selectedYear) + '&nonce=' + encodeURIComponent(githubCommitChartSettings.nonce);


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
                        renderCommitChart(container, githubProfile, response.data, selectedYear);
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
    function renderCommitChart(container, githubProfile, commitData, selectedYear) {
        // Получаем текущий год
        var currentYear = new Date().getFullYear();

        // Если год не указан, используем текущий
        if (!selectedYear) {
            selectedYear = currentYear;
        }

        // Получаем тег заголовка из data-атрибута или используем значение по умолчанию
        var headingTag = container.getAttribute('data-heading-tag') || 'h3';

        // Проверяем настройку для ссылок на профили GitHub
        var linkUsernames = githubCommitChartSettings.linkUsernames || false;
        var profileLink = linkUsernames ? '<a href="https://github.com/' + githubProfile + '" target="_blank" rel="noopener noreferrer">' + githubProfile + '</a>' : githubProfile;

        // Создаем элементы диаграммы
        var chartHTML = '<div class="github-commit-chart">';

        // Если выбран обычный текст (div), отображаем центрированный текст без тега заголовка
        if (headingTag === 'div') {
            chartHTML += '<div class="github-commit-chart-text">Диаграмма коммитов для ' + profileLink + '</div>';
        } else {
            // Иначе используем выбранный тег заголовка
            chartHTML += '<' + headingTag + '>Диаграмма коммитов для ' + profileLink + '</' + headingTag + '>';
        }

        // Добавляем селектор года
        chartHTML += '<div class="year-selector">';
        for (var i = 5; i >= 0; i--) {
            var year = currentYear - i;
            var isActive = (year === selectedYear) ? ' active' : '';
            chartHTML += '<button class="year-button' + isActive + '" data-year="' + year + '">' + year + '</button>';
        }
        chartHTML += '</div>';

        chartHTML += '<div class="chart-container">';
        chartHTML += '</div>';

        chartHTML += '</div>';

        container.innerHTML = chartHTML;

        // Добавляем обработчики событий для кнопок года
        setTimeout(function() {
            var yearButtons = container.querySelectorAll('.year-button');
            yearButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var year = parseInt(this.getAttribute('data-year'));

                    // Показываем плейсхолдер только для контейнера диаграммы
                    var chartContainer = container.querySelector('.chart-container');
                    if (chartContainer) {
                        // Сохраняем текущую высоту для плавного перехода
                        var currentHeight = chartContainer.offsetHeight;
                        chartContainer.style.minHeight = currentHeight + 'px';

                        // Показываем плейсхолдер
                        chartContainer.innerHTML = '<div class="chart-loading-placeholder"><div class="loading-spinner"></div><p>Идёт загрузка коммитов</p></div>';
                    }

                    // Загружаем данные за выбранный год
                    createCommitChart(container, githubProfile, year);
                });
            });
        }, 100);

        // Создаем визуализацию данных в виде тепловой карты
        renderCommitChartHeatmap(container, commitData, selectedYear);
    }

    // Функция для отображения диаграммы в виде тепловой карты
    function renderCommitChartHeatmap(container, commitData, selectedYear) {
        // Если год не указан, используем логику по умолчанию (последние 365 дней)
        if (!selectedYear) {
            selectedYear = new Date().getFullYear();
        }
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

        // Определяем начальную и конечную даты на основе выбранного года
        var endDate, startDate;
        if (selectedYear === new Date().getFullYear()) {
            // Для текущего года - последние 365 дней
            endDate = new Date();
            startDate = new Date();
            startDate.setDate(endDate.getDate() - 364);
        } else {
            // Для предыдущих лет - весь год с 1 января по 31 декабря
            endDate = new Date(selectedYear, 11, 31); // 31 декабря выбранного года
            startDate = new Date(selectedYear, 0, 1); // 1 января выбранного года
        }

        // Всегда начинаем с понедельника первой недели года, чтобы заполнить всю сетку
        var jan1 = new Date(selectedYear, 0, 1);
        var dayOfWeek = jan1.getDay();
        if (dayOfWeek === 0) dayOfWeek = 7; // Воскресенье = 7
        var yearStartMonday = new Date(jan1);
        yearStartMonday.setDate(jan1.getDate() - (dayOfWeek - 1)); // Устанавливаем на понедельник

        // Используем yearStartMonday как базовую начальную дату для консистентности
        startDate = yearStartMonday;

        // Рассчитываем количество дней и недель - всегда используем полный год (53 недели максимум)
        var yearEnd = new Date(selectedYear, 11, 31);
        var yearStart = new Date(selectedYear, 0, 1);
        var timeDiff = yearEnd.getTime() - yearStartMonday.getTime();
        var daysTotal = Math.floor(timeDiff / (1000 * 3600 * 24)) + 1;
        var weeksCount = Math.min(Math.ceil(daysTotal / 7), 53); // Ограничиваем 53 неделями для полного года

        // Создаем заголовки месяцев
        html += '<div class="heatmap-row month-labels">';
        html += '<div class="heatmap-label"></div>'; // Пустая ячейка для выравнивания

        // Создаем отображение месяцев
        var currentDate = new Date(startDate);
        var monthStarts = []; // Храним позиции начала месяцев

        // Определяем позиции начала месяцев
        var maxWeeksForMonths = selectedYear === new Date().getFullYear() ? weeksCount : Math.min(weeksCount, 53);
        for (var week = 0; week < maxWeeksForMonths; week++) {
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

        // Заполняем оставшиеся ячейки до конца года (53 недели)
        for (var i = lastPosition; i < weeksCount; i++) {
            html += '<div class="heatmap-label"></div>';
        }

        html += '</div>';

        // Создаем строки для каждой недели
        var currentDate = new Date(startDate);

        // Создаем 7 строк для дней недели (Пн-Вс)
        var daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        for (var dayIndex = 0; dayIndex < 7; dayIndex++) {
            html += '<div class="heatmap-row">';

            // Добавляем метку дня недели
            html += '<div class="heatmap-label">' + daysOfWeek[dayIndex] + '</div>';

            // Создаем ячейки для каждого дня от startDate до endDate
            var currentWeek = new Date(currentDate);
            // Устанавливаем на понедельник той недели, в которой находится startDate
            var dayOfWeek = currentWeek.getDay();
            if (dayOfWeek === 0) dayOfWeek = 7; // Воскресенье = 7
            currentWeek.setDate(currentWeek.getDate() - (dayOfWeek - 1) + dayIndex);

            // Отображаем недели от startDate до endDate (всегда полный год)
            for (var week = 0; week < weeksCount; week++) {
                var dateStr = currentWeek.toISOString().split('T')[0];
                var commits = commitData[dateStr] || 0;

                // Определяем интенсивность цвета (от 0 до 4)
                var intensity = 0;
                if (maxCommits > 0 && commits > 0) {
                    intensity = Math.ceil((commits / maxCommits) * 4);
                    if (intensity > 4) intensity = 4;
                    if (intensity < 1) intensity = 1;
                }

                // Проверяем, что дата находится в выбранном году
                var cellDate = new Date(dateStr);
                var cellYear = cellDate.getFullYear();

                if (cellYear === selectedYear && cellDate <= endDate) {
                    // Дата в выбранном году и не превышает endDate - показываем коммиты
                    html += '<div class="heatmap-cell intensity-' + intensity + '" title="' + dateStr + ': ' + commits + ' коммитов"></div>';
                } else {
                    // Дата вне диапазона - пустая ячейка
                    html += '<div class="heatmap-cell"></div>';
                }

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