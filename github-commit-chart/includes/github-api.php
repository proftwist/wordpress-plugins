<?php
// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('GitHubCommitChart_API')) {

class GitHubCommitChart_API {
    
    private static $api_url = 'https://api.github.com';
    private static $cache_key_prefix = 'gcc_github_data_';
    private static $cache_expiration = 3600; // 1 час
    
    /**
     * Получение данных о коммитах пользователя
     */
    /**
     * Обработка ошибок API GitHub
     */
    private static function handle_api_error($data) {
        $error_message = isset($data['message']) ? $data['message'] : 'Неизвестная ошибка';
        return new WP_Error('github_api_error', 'Ошибка GitHub API: ' . $error_message);
    }
    
    public static function get_user_commits($username) {
        // Проверяем кэш
        $cache_key = self::$cache_key_prefix . 'commits_' . $username;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Получаем список репозиториев пользователя
        $repos = self::get_user_repos($username);
        
        if (is_wp_error($repos)) {
            return $repos;
        }
        
        // Собираем коммиты из всех репозиториев
        $all_commits = array();
        
        foreach ($repos as $repo) {
            $commits = self::get_repo_commits($username, $repo['name']);
            
            if (!is_wp_error($commits)) {
                $all_commits = array_merge($all_commits, $commits);
            }
        }
        
        // Сортируем коммиты по дате
        usort($all_commits, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Кэшируем результат
        set_transient($cache_key, $all_commits, self::$cache_expiration);
        
        return $all_commits;
    }
    
    /**
     * Получение списка репозиториев пользователя
     */
    private static function get_user_repos($username) {
        $cache_key = self::$cache_key_prefix . 'repos_' . $username;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $url = self::$api_url . '/users/' . $username . '/repos?per_page=100';
        $response = wp_remote_get($url, array(
            'headers' => array(
                'User-Agent' => 'GitHub-Commit-Chart-WordPress-Plugin',
                'Accept' => 'application/vnd.github.v3+json'
            ),
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
        
        $repos = array();
        foreach ($data as $repo) {
            $repos[] = array(
                'name' => $repo['name'],
                'full_name' => $repo['full_name'],
                'private' => $repo['private']
            );
        }
        
        // Кэшируем результат
        set_transient($cache_key, $repos, self::$cache_expiration);
        
        return $repos;
    }
    
    /**
     * Получение коммитов репозитория
     */
    private static function get_repo_commits($username, $repo_name) {
        $cache_key = self::$cache_key_prefix . 'commits_' . $username . '_' . $repo_name;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $url = self::$api_url . '/repos/' . $username . '/' . $repo_name . '/commits?per_page=100';
        $response = wp_remote_get($url, array(
            'headers' => array(
                'User-Agent' => 'GitHub-Commit-Chart-WordPress-Plugin',
                'Accept' => 'application/vnd.github.v3+json'
            ),
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
        
        $commits = array();
        foreach ($data as $commit) {
            $commits[] = array(
                'sha' => substr($commit['sha'], 0, 7), // Сокращенный SHA
                'message' => $commit['commit']['message'],
                'date' => $commit['commit']['author']['date'],
                'author' => $commit['commit']['author']['name'],
                'repo' => $repo_name
            );
        }
        
        // Кэшируем результат
        set_transient($cache_key, $commits, self::$cache_expiration);
        
        return $commits;
    }
    
    /**
     * Получение статистики коммитов по дням за последние 365 дней
     */
    public static function get_commit_stats($username) {
        $cache_key = self::$cache_key_prefix . 'stats_' . $username;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $commits = self::get_user_commits($username);
        
        // Проверяем ошибки
        if (is_wp_error($commits)) {
            return $commits;
        }
        
        // Проверяем, является ли результат массивом с ошибкой
        if (is_array($commits) && isset($commits['error'])) {
            return self::handle_api_error($commits);
        }
        
        // Создаем массив для статистики по дням
        $stats = array();
        $today = new DateTime();
        $year_ago = clone $today;
        $year_ago->modify('-365 days');
        
        // Инициализируем все дни последнего года
        $current_date = clone $year_ago;
        while ($current_date <= $today) {
            $stats[$current_date->format('Y-m-d')] = 0;
            $current_date->modify('+1 day');
        }
        
        // Подсчитываем коммиты по дням
        foreach ($commits as $commit) {
            $commit_date = new DateTime($commit['date']);
            $commit_date_str = $commit_date->format('Y-m-d');
            
            // Проверяем, что дата в диапазоне последнего года
            if ($commit_date >= $year_ago && $commit_date <= $today) {
                if (isset($stats[$commit_date_str])) {
                    $stats[$commit_date_str]++;
                }
            }
        }
        
        // Кэшируем результат
        set_transient($cache_key, $stats, self::$cache_expiration);
        
        return $stats;
    }
    
    /**
     * Очистка кэша для пользователя
     */
    public static function clear_cache($username) {
        delete_transient(self::$cache_key_prefix . 'commits_' . $username);
        delete_transient(self::$cache_key_prefix . 'repos_' . $username);
        delete_transient(self::$cache_key_prefix . 'stats_' . $username);
        
        // Очищаем кэш для всех репозиториев пользователя
        $repos = self::get_user_repos($username);
        if (!is_wp_error($repos) && !(is_array($repos) && isset($repos['error']))) {
            foreach ($repos as $repo) {
                delete_transient(self::$cache_key_prefix . 'commits_' . $username . '_' . $repo['name']);
            }
        }
    }
}

} // class_exists check