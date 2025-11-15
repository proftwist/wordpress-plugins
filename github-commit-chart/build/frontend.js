/**
 * GitHub Commit Chart Frontend JavaScript
 * 
 * Handles the frontend functionality for displaying GitHub commit charts
 * 
 * @package GitHubCommitChart
 * @since 1.0.0
 */

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

    /**
     * Функция для создания диаграммы коммитов
     * 
     * @param {HTMLElement} container - Контейнер для диаграммы
     * @param {string} githubProfile - Имя пользователя GitHub
     * @param {number|null} selectedYear - Выбранный год (по умолчанию null)
     */
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

        // Проверяем, существует ли уже статическая часть диаграммы
        var chartElement = container.querySelector('.github-commit-chart');
        if (!chartElement) {
            // Если нет, создаем базовую структуру
            renderCommitChartStructure(container, githubProfile, selectedYear);
            
            // Показываем плейсхолдер для области диаграммы
            setTimeout(function() {
                var chartContainer = container.querySelector('.chart-container');
                if (chartContainer) {
                    chartContainer.innerHTML = '<div class="chart-loading-placeholder"><div class="loading-spinner"></div><p>Идёт загрузка коммитов…</p></div>';
                }
            }, 100);
        } else {
            // Если да, показываем плейсхолдер только для области диаграммы
            var chartContainer = container.querySelector('.chart-container');
            if (chartContainer) {
                // Сохраняем текущую высоту для плавного перехода
                var currentHeight = chartContainer.offsetHeight;
                chartContainer.style.minHeight = currentHeight + 'px';
                
                // Показываем плейсхолдер с небольшой задержкой для лучшего UX
                setTimeout(function() {
                    chartContainer.innerHTML = '<div class="chart-loading-placeholder"><div class="loading-spinner"></div><p>Идёт загрузка коммитов…</p></div>';
                }, 100);
            }
        }

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

    /**
     * Функция для отображения структуры диаграммы коммитов (заголовок и селекторы лет)
     * 
     * @param {HTMLElement} container - Контейнер для диаграммы
     * @param {string} githubProfile - Имя пользователя GitHub
     * @param {number} selectedYear - Выбранный год
     */
    function renderCommitChartStructure(container, githubProfile, selectedYear) {
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
                    // Убираем класс active у всех кнопок
                    yearButtons.forEach(function(btn) {
                        btn.classList.remove('active');
                    });
                    // Добавляем класс active к выбранной кнопке
                    this.classList.add('active');

                    // Показываем плейсхолдер только для контейнера диаграммы
                    var chartContainer = container.querySelector('.chart-container');
                    if (chartContainer) {
                        // Сохраняем текущую высоту для плавного перехода
                        var currentHeight = chartContainer.offsetHeight;
                        chartContainer.style.minHeight = currentHeight + 'px';
                        
                        // Показываем плейсхолдер с небольшой задержкой для лучшего UX
                        setTimeout(function() {
                            chartContainer.innerHTML = '<div class="chart-loading-placeholder"><div class="loading-spinner"></div><p>Идёт загрузка коммитов…</p></div>';
                        }, 100);
                    }

                    // Загружаем данные за выбранный год
                    createCommitChart(container, githubProfile, year);
                });
            });
        }, 100);
    }

    /**
     * Функция для отображения диаграммы коммитов
     * 
     * @param {HTMLElement} container - Контейнер для диаграммы
     * @param {string} githubProfile - Имя пользователя GitHub
     * @param {Object} commitData - Данные о коммитах
     * @param {number} selectedYear - Выбранный год
     */
    function renderCommitChart(container, githubProfile, commitData, selectedYear) {
        // Получаем текущий год
        var currentYear = new Date().getFullYear();

        // Если год не указан, используем текущий
        if (!selectedYear) {
            selectedYear = currentYear;
        }

        // Проверяем, существует ли уже статическая часть диаграммы
        var chartElement = container.querySelector('.github-commit-chart');
        if (!chartElement) {
            // Если нет, создаем базовую структуру
            renderCommitChartStructure(container, githubProfile, selectedYear);
        } else {
            // Если да, обновляем активную кнопку года
            var yearButtons = container.querySelectorAll('.year-button');
            yearButtons.forEach(function(button) {
                var buttonYear = parseInt(button.getAttribute('data-year'));
                if (buttonYear === selectedYear) {
                    button.classList.add('active');
                } else {
                    button.classList.remove('active');
                }
            });
        }

        // Создаем визуализацию данных в виде тепловой карты только в области диаграммы
        renderCommitChartHeatmap(container, commitData, selectedYear);
    }

    /**
     * Функция для отображения диаграммы в виде тепловой карты
     * 
     * @param {HTMLElement} container - Контейнер для диаграммы
     * @param {Object} commitData - Данные о коммитах
     * @param {number} selectedYear - Выбранный год
     */
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

        // Создаем отображение месяцев - всегда отображаем все 12 месяцев
        var monthPositions = []; // Храним позиции начала каждого месяца
        
        // Определяем позиции начала каждого месяца
        var jan1 = new Date(selectedYear, 0, 1);
        var firstMonday = new Date(jan1);
        var dayOfWeek = firstMonday.getDay();
        if (dayOfWeek === 0) dayOfWeek = 7; // Воскресенье = 7
        firstMonday.setDate(jan1.getDate() - (dayOfWeek - 1)); // Устанавливаем на понедельник
        
        // Для каждого месяца определяем его позицию (неделю) начала
        for (var monthIndex = 0; monthIndex < 12; monthIndex++) {
            var monthStart = new Date(selectedYear, monthIndex, 1);
            var daysFromStart = Math.floor((monthStart - firstMonday) / (1000 * 3600 * 24));
            var weekPosition = Math.floor(daysFromStart / 7);
            
            monthPositions[monthIndex] = {
                week: weekPosition,
                name: months[monthIndex]
            };
        }

        // Создаем ячейки для месяцев
        var lastPosition = 0;
        
        // Отображаем все 12 месяцев
        for (var monthIndex = 0; monthIndex < 12; monthIndex++) {
            var monthInfo = monthPositions[monthIndex];
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
        // Создаем 7 строк для дней недели (Пн-Вс)
        var daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        for (var dayIndex = 0; dayIndex < 7; dayIndex++) {
            html += '<div class="heatmap-row">';

            // Добавляем метку дня недели
            html += '<div class="heatmap-label">' + daysOfWeek[dayIndex] + '</div>';

            // Создаем ячейки для каждого дня недели на протяжении всего года
            // Для текущего года отображаем только прошедшие недели, для прошлых лет - весь год
            var weeksToShow = 53;
            if (selectedYear === new Date().getFullYear()) {
                // Для текущего года вычисляем количество прошедших недель
                var today = new Date();
                var firstDayOfYear = new Date(selectedYear, 0, 1);
                var daysPassed = Math.floor((today - firstDayOfYear) / (1000 * 3600 * 24));
                weeksToShow = Math.min(Math.ceil(daysPassed / 7) + 1, 53); // +1 для полноты недели
            }
            
            for (var week = 0; week < weeksToShow; week++) {
                // Вычисляем дату для текущей ячейки
                var cellDate = new Date(firstMonday);
                cellDate.setDate(firstMonday.getDate() + (week * 7) + dayIndex);
                var dateStr = cellDate.toISOString().split('T')[0];
                var commits = commitData[dateStr] || 0;

                // Определяем интенсивность цвета (от 0 до 4)
                var intensity = 0;
                if (maxCommits > 0 && commits > 0) {
                    intensity = Math.ceil((commits / maxCommits) * 4);
                    if (intensity > 4) intensity = 4;
                    if (intensity < 1) intensity = 1;
                }

                // Проверяем, что дата находится в выбранном году
                var cellYear = cellDate.getFullYear();

                if (cellYear === selectedYear) {
                    // Дата в выбранном году - показываем коммиты или пустую ячейку
                    if (commits > 0) {
                        html += '<div class="heatmap-cell intensity-' + intensity + '" title="' + dateStr + ': ' + commits + ' коммитов"></div>';
                    } else {
                        html += '<div class="heatmap-cell intensity-0" title="' + dateStr + ': 0 коммитов"></div>';
                    }
                } else {
                    // Дата вне выбранного года - пустая ячейка с интенсивностью 0
                    html += '<div class="heatmap-cell intensity-0"></div>';
                }
            }
            
            // Заполняем оставшиеся ячейки пустыми блоками до 53 недель
            for (var week = weeksToShow; week < 53; week++) {
                html += '<div class="heatmap-cell intensity-0"></div>';
            }

            html += '</div>';
        }

        html += '</div>';

        // Вставляем сгенерированный HTML в контейнер с плавным переходом
        // Удаляем минимальную высоту после небольшой задержки для плавного перехода
        chartContainer.innerHTML = html;
        setTimeout(function() {
            chartContainer.style.minHeight = '';
        }, 300);
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