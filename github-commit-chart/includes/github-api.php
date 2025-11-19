<?php
/**
 * GitHub API handler for GitHub Commit Chart plugin
 *
 * @package GitHubCommitChart
 * @since 1.0.0
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('GitHubCommitChart_API')) {

    /**
     * Class for handling GitHub API requests
     *
     * This class handles all GitHub API interactions for the GitHub Commit Chart plugin,
     * including data retrieval, caching, and error handling.
     *
     * @package GitHubCommitChart
     * @since 1.0.0
     */
    class GitHubCommitChart_API {

        /**
         * GitHub API base URL
         *
         * @var string
         * @since 1.0.0
         */
        private static $api_url = 'https://api.github.com';

        /**
         * Cache key prefix for transients
         *
         * @var string
         * @since 1.0.0
         */
        private static $cache_key_prefix = 'gcc_github_data_';

        /**
         * Cache expiration time in seconds (1 hour)
         *
         * @var int
         * @since 1.0.0
         */
        private static $cache_expiration = 3600;

        /**
         * Get headers for API requests
         *
         * @return array Array of headers for API requests
         * @since 1.0.0
         */
        private static function get_api_headers() {
            $headers = array(
                'User-Agent' => 'GitHub-Commit-Chart-WordPress-Plugin',
                'Accept' => 'application/vnd.github.v3+json'
            );

            // Добавляем токен авторизации, если он установлен и функция доступна
            if (function_exists('get_option')) {
                $token = get_option('github_commit_chart_github_token');
                if (!empty($token)) {
                    $headers['Authorization'] = 'token ' . $token;
                }
            }

            return $headers;
        }

        /**
         * Handle GitHub API errors
         *
         * @param array $data Error data from GitHub API
         * @return WP_Error|Exception WordPress error object or Exception
         * @since 1.0.0
         */
        private static function handle_api_error($data) {
            $error_message = isset($data['message']) ? $data['message'] : 'Неизвестная ошибка';

            // Добавляем информацию о лимитах API, если доступна
            if (isset($data['documentation_url'])) {
                $error_message .= ' Подробнее: ' . $data['documentation_url'];
            }

            // Возвращаем WP_Error если функция доступна, иначе создаем исключение
            if (class_exists('WP_Error')) {
                return new WP_Error('github_api_error', 'Ошибка GitHub API: ' . $error_message);
            } else {
                return new Exception('Ошибка GitHub API: ' . $error_message);
            }
        }

        /**
         * Get user commits from all repositories
         *
         * @param string $username GitHub username
         * @return array|WP_Error Array of commits or WP_Error on failure
         * @since 1.8.4
         */
        public static function get_user_commits($username) {
            // Проверяем кэш если функция доступна
            if (function_exists('get_transient')) {
                $cache_key = self::$cache_key_prefix . 'commits_' . $username;
                $cached_data = get_transient($cache_key);

                if ($cached_data !== false) {
                    return $cached_data;
                }
            }

            // Получаем список репозиториев пользователя
            $repos = self::get_user_repos($username);

            if (function_exists('is_wp_error') && is_wp_error($repos)) {
                return $repos;
            }

            // Собираем коммиты из всех репозиториев
            $all_commits = array();

            foreach ($repos as $repo) {
                $commits = self::get_repo_commits($username, $repo['name']);

                if (!(function_exists('is_wp_error') && is_wp_error($commits))) {
                    $all_commits = array_merge($all_commits, $commits);
                }
            }

            // Сортируем коммиты по дате
            usort($all_commits, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            // Кэшируем результат если функция доступна
            if (function_exists('set_transient')) {
                $cache_key = self::$cache_key_prefix . 'commits_' . $username;
                set_transient($cache_key, $all_commits, self::$cache_expiration);
            }

            return $all_commits;
        }

        /**
         * Get user repositories
         *
         * @param string $username GitHub username
         * @return array|WP_Error Array of repositories or WP_Error on failure
         * @since 1.8.4
         */
        private static function get_user_repos($username) {
            // Проверяем кэш если функция доступна
            if (function_exists('get_transient')) {
                $cache_key = self::$cache_key_prefix . 'repos_' . $username;
                $cached_data = get_transient($cache_key);

                if ($cached_data !== false) {
                    return $cached_data;
                }
            }

            $url = self::$api_url . '/users/' . $username . '/repos?per_page=100&sort=updated&direction=desc';

            // Выполняем запрос если функция доступна
            if (function_exists('wp_remote_get')) {
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

                $repos = array();
                foreach ($data as $repo) {
                    // Пропускаем только архивные репозитории, форки включаем
                    if (!$repo['archived']) {
                        $repos[] = array(
                            'name' => $repo['name'],
                            'full_name' => $repo['full_name'],
                            'private' => $repo['private']
                        );
                    }
                }

                // Кэшируем результат если функция доступна
                if (function_exists('set_transient')) {
                    $cache_key = self::$cache_key_prefix . 'repos_' . $username;
                    set_transient($cache_key, $repos, self::$cache_expiration);
                }

                return $repos;
            } else {
                // Если WordPress функции недоступны, возвращаем пустой массив
                return array();
            }
        }

        /**
         * Get commits from a specific repository
         *
         * @param string $username GitHub username
         * @param string $repo_name Repository name
         * @return array|WP_Error Array of commits or WP_Error on failure
         * @since 1.0.0
         */
        private static function get_repo_commits($username, $repo_name) {
            // Проверяем кэш если функция доступна
            if (function_exists('get_transient')) {
                $cache_key = self::$cache_key_prefix . 'commits_' . $username . '_' . $repo_name;
                $cached_data = get_transient($cache_key);

                if ($cached_data !== false) {
                    return $cached_data;
                }
            }

            $all_commits = array();
            $page = 1;
            $has_more = true;

            while ($has_more) {
                $url = self::$api_url . '/repos/' . $username . '/' . $repo_name . '/commits?per_page=100&page=' . $page . '&sort=author-date&order=desc&author=' . $username;

                // Выполняем запрос если функция доступна
                if (function_exists('wp_remote_get')) {
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

                    // Если нет коммитов на странице или меньше 100, это последняя страница
                    if (empty($data) || count($data) < 100) {
                        $has_more = false;
                    }

                    foreach ($data as $commit) {
                        $all_commits[] = array(
                            'sha' => substr($commit['sha'], 0, 7), // Сокращенный SHA
                            'message' => $commit['commit']['message'],
                            'date' => $commit['commit']['author']['date'],
                            'author' => $commit['commit']['author']['name'],
                            'repo' => $repo_name
                        );
                    }

                    $page++;

                    // Защита от бесконечного цикла - максимум 10 страниц (1000 коммитов)
                    if ($page > 10) {
                        break;
                    }
                } else {
                    // Если WordPress функции недоступны, выходим из цикла
                    $has_more = false;
                }
            }

            // Кэшируем результат если функция доступна
            if (function_exists('set_transient')) {
                $cache_key = self::$cache_key_prefix . 'commits_' . $username . '_' . $repo_name;
                set_transient($cache_key, $all_commits, self::$cache_expiration);
            }

            return $all_commits;
        }

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
                if (empty($data) || !is_array($data) || count($data) < 100) {
                    $has_more = false;
                }

                // Фильтруем только релевантные события
                if (is_array($data)) {
                    foreach ($data as $event) {
                        if (self::is_relevant_event($event)) {
                            $all_events[] = $event;
                        }
                    }
                }

                $page++;

                // Защита от бесконечного цикла - максимум 5 страниц (500 событий)
                if ($page > 5) {
                    break;
                }
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
            // Проверяем что event существует и имеет тип
            if (!is_array($event) || !isset($event['type'])) {
                return false;
            }

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
            // Проверяем что event существует и имеет тип
            if (!is_array($event) || !isset($event['type'])) {
                return 1;
            }

            // Проверяем что payload существует если нужен
            $has_payload = isset($event['payload']) && is_array($event['payload']);

            switch ($event['type']) {
                case 'PushEvent':
                    // Каждый коммит считается отдельно, но только если поле существует
                    if ($has_payload && isset($event['payload']['commits']) && is_array($event['payload']['commits'])) {
                        return count($event['payload']['commits']);
                    }
                    return 1;

                case 'PullRequestEvent':
                    // PR считается как 2 активности (создание + работа над ним)
                    if ($has_payload && isset($event['payload']['action']) && $event['payload']['action'] === 'opened') {
                        return 2;
                    }
                    return 1;

                case 'IssuesEvent':
                    // Issue считается как 2 активности (создание + обсуждение)
                    if ($has_payload && isset($event['payload']['action']) && $event['payload']['action'] === 'opened') {
                        return 2;
                    }
                    return 1;

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

        /**
         * Check if a GitHub user exists
         *
         * @param string $username GitHub username
         * @return bool True if user exists, false otherwise
         * @since 1.0.0
         */
        public static function check_user_exists($username) {
            // Проверяем кэш если функция доступна
            if (function_exists('get_transient')) {
                $cache_key = self::$cache_key_prefix . 'user_exists_' . $username;
                $cached_data = get_transient($cache_key);

                if ($cached_data !== false) {
                    return $cached_data;
                }
            }

            // Выполняем запрос если функция доступна
            if (function_exists('wp_remote_get')) {
                $url = self::$api_url . '/users/' . $username;
                $response = wp_remote_get($url, array(
                    'headers' => self::get_api_headers(),
                    'timeout' => 30
                ));

                if (is_wp_error($response)) {
                    return false;
                }

                $status_code = wp_remote_retrieve_response_code($response);

                $exists = ($status_code === 200);

                // Кэшируем результат на короткое время (5 минут) если функция доступна
                if (function_exists('set_transient')) {
                    $cache_key = self::$cache_key_prefix . 'user_exists_' . $username;
                    set_transient($cache_key, $exists, 300);
                }

                return $exists;
            } else {
                // Если WordPress функции недоступны, возвращаем false
                return false;
            }
        }

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

        /**
         * Get API rate limit information
         *
         * @return array|WP_Error Array with rate limit information or WP_Error on failure
         * @since 1.8.3
         */
        public static function get_rate_limit() {
            // Выполняем запрос если функция доступна
            if (function_exists('wp_remote_get')) {
                $url = self::$api_url . '/rate_limit';
                $response = wp_remote_get($url, array(
                    'headers' => self::get_api_headers(),
                    'timeout' => 30
                ));

                if (is_wp_error($response)) {
                    return $response;
                }

                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (wp_remote_retrieve_response_code($response) !== 200) {
                    return self::handle_api_error($data);
                }

                return $data;
            } else {
                // Если WordPress функции недоступны, возвращаем пустой массив
                return array();
            }
        }
    }
}

// class_exists check