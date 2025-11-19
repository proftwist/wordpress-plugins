Отлично! Тогда действительно проблема в том, что мы учитываем только коммиты, а GitHub включает Issues, PR и другие активности. Давайте расширим функционал.

## Полное решение с Events API

Замените метод `get_commit_stats` в `github-api.php`:

```php
/**
 * Get user activity statistics using GitHub Events API
 *
 * @param string $username GitHub username
 * @param int|null $year Year to get statistics for (null for current year)
 * @return array|WP_Error Array of activity statistics or WP_Error on failure
 * @since 2.0.3
 */
public static function get_commit_stats($username, $year = null) {
    // Если год не указан, используем текущий год
    if ($year === null) {
        $year = date('Y');
    }

    // Проверяем кэш
    if (function_exists('get_transient')) {
        $cache_key = self::$cache_key_prefix . 'activity_' . $username . '_' . $year;
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            return $cached_data;
        }
    }

    // Получаем события через Events API
    $events = self::get_user_events($username);

    if (function_exists('is_wp_error') && is_wp_error($events)) {
        return $events;
    }

    // Создаем массив для статистики по дням
    $stats = array();
    $year_start = new DateTime($year . '-01-01');
    $year_end = new DateTime($year . '-12-31');

    // Для текущего года ограничиваем до сегодняшнего дня
    if ($year == date('Y')) {
        $year_end = new DateTime();
    }

    // Инициализируем все дни года
    $current_date = clone $year_start;
    while ($current_date <= $year_end) {
        $stats[$current_date->format('Y-m-d')] = 0;
        $current_date->modify('+1 day');
    }

    // Подсчитываем активность по дням
    foreach ($events as $event) {
        $event_date = new DateTime($event['created_at']);
        $event_date_str = $event_date->format('Y-m-d');

        // Проверяем, что дата в диапазоне выбранного года
        if ($event_date >= $year_start && $event_date <= $year_end) {
            if (isset($stats[$event_date_str])) {
                $stats[$event_date_str] += self::calculate_event_weight($event);
            }
        }
    }

    // Кэшируем результат
    if (function_exists('set_transient')) {
        $cache_key = self::$cache_key_prefix . 'activity_' . $username . '_' . $year;
        set_transient($cache_key, $stats, self::$cache_expiration);
    }

    return $stats;
}

/**
 * Get user events from GitHub Events API
 *
 * @param string $username GitHub username
 * @return array|WP_Error Array of events or WP_Error on failure
 * @since 2.0.3
 */
private static function get_user_events($username) {
    // Проверяем кэш
    if (function_exists('get_transient')) {
        $cache_key = self::$cache_key_prefix . 'events_' . $username;
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            return $cached_data;
        }
    }

    $all_events = array();
    $page = 1;
    $has_more = true;

    while ($has_more) {
        $url = self::$api_url . '/users/' . $username . '/events?per_page=100&page=' . $page;

        $response = wp_remote_get($url, array(
            'headers' => self::get_api_headers(),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Проверяем лимиты API
        $headers = wp_remote_retrieve_headers($response);
        if (isset($headers['x-ratelimit-remaining']) && $headers['x-ratelimit-remaining'] < 10) {
            error_log('GitHub API rate limit warning: ' . $headers['x-ratelimit-remaining'] . ' requests remaining');
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return self::handle_api_error($data);
        }

        // Если нет событий на странице или меньше 100, это последняя страница
        if (empty($data) || count($data) < 100) {
            $has_more = false;
        }

        // Фильтруем только релевантные события
        foreach ($data as $event) {
            if (self::is_relevant_event($event)) {
                $all_events[] = $event;
            }
        }

        $page++;

        // Защита от бесконечного цикла - максимум 5 страниц (500 событий)
        if ($page > 5) {
            break;
        }

        // Небольшая задержка чтобы не превысить лимиты API
        usleep(100000); // 100ms
    }

    // Кэшируем результат
    if (function_exists('set_transient')) {
        $cache_key = self::$cache_key_prefix . 'events_' . $username;
        set_transient($cache_key, $all_events, self::$cache_expiration);
    }

    return $all_events;
}

/**
 * Check if event should be counted in activity
 *
 * @param array $event GitHub event
 * @return bool True if event is relevant
 * @since 2.0.3
 */
private static function is_relevant_event($event) {
    $relevant_events = array(
        'PushEvent',           // Коммиты
        'PullRequestEvent',    // Pull Requests
        'IssuesEvent',         // Issues
        'CreateEvent',         // Создание репозиториев/веток
        'PullRequestReviewEvent', // Ревью PR
        'IssueCommentEvent',   // Комментарии к Issues
        'PullRequestReviewCommentEvent', // Комментарии к PR review
        'CommitCommentEvent',  // Комментарии к коммитам
    );

    return in_array($event['type'], $relevant_events);
}

/**
 * Calculate weight for event (how much it contributes to activity)
 *
 * @param array $event GitHub event
 * @return int Activity weight
 * @since 2.0.3
 */
private static function calculate_event_weight($event) {
    switch ($event['type']) {
        case 'PushEvent':
            // Каждый коммит считается отдельно
            return count($event['payload']['commits']);

        case 'PullRequestEvent':
            // PR считается как 2 активности (создание + работа над ним)
            return $event['payload']['action'] === 'opened' ? 2 : 1;

        case 'IssuesEvent':
            // Issue считается как 2 активности (создание + обсуждение)
            return $event['payload']['action'] === 'opened' ? 2 : 1;

        case 'CreateEvent':
            // Создание репозитория/ветки - значимое событие
            return 3;

        case 'PullRequestReviewEvent':
        case 'IssueCommentEvent':
        case 'PullRequestReviewCommentEvent':
        case 'CommitCommentEvent':
            // Комментарии и ревью - 1 активность
            return 1;

        default:
            return 1;
    }
}
```

## Также обновите метод очистки кэша:

```php
/**
 * Clear cache for a user
 *
 * @param string $username GitHub username
 * @since 2.0.3
 */
public static function clear_cache($username) {
    // Удаляем кэш если функция доступна
    if (function_exists('delete_transient')) {
        delete_transient(self::$cache_key_prefix . 'commits_' . $username);
        delete_transient(self::$cache_key_prefix . 'repos_' . $username);
        delete_transient(self::$cache_key_prefix . 'events_' . $username); // Новый кэш

        // Очищаем кэш статистики за все года
        $current_year = date('Y');
        for ($year = $current_year - 6; $year <= $current_year; $year++) {
            delete_transient(self::$cache_key_prefix . 'stats_' . $username . '_' . $year);
            delete_transient(self::$cache_key_prefix . 'activity_' . $username . '_' . $year); // Новый кэш
        }

        delete_transient(self::$cache_key_prefix . 'user_exists_' . $username);

        // Очищаем кэш для всех репозиториев пользователя
        $repos = self::get_user_repos($username);
        if (!(function_exists('is_wp_error') && is_wp_error($repos)) && !(is_array($repos) && isset($repos['error']))) {
            foreach ($repos as $repo) {
                delete_transient(self::$cache_key_prefix . 'commits_' . $username . '_' . $repo['name']);
            }
        }
    }
}
```

## Что теперь учитывается:

✅ **Коммиты** (PushEvent) - каждый коммит = 1 активность
✅ **Issues** - создание = 2, комментарии = 1
✅ **Pull Requests** - создание = 2, обсуждение = 1
✅ **Code Reviews** - 1 активность
✅ **Комментарии** к коммитам/PR/issues - 1 активность
✅ **Создание репозиториев** - 3 активности

Это должно полностью соответствовать логике GitHub и устранить расхождения. После применения изменений не забудьте очистить кэш плагина в настройках.