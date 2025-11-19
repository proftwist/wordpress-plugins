Проанализировав код плагина, я нашел несколько проблем в логике подсчета коммитов, которые приводят к расхождениям с GitHub.

## Основные проблемы:

### 1. **Неправильная фильтрация по коммиттеру**
В файле `github-api.php` используется параметр `committer` вместо `author`:

```php
// Строка 232 в github-api.php
$url = self::$api_url . '/repos/' . $username . '/' . $repo_name . '/commits?per_page=100&sort=author-date&order=desc&committer=' . $username;
```

**Проблема:** Параметр `committer` фильтрует по тому, кто выполнил коммит (может отличаться от автора), а `author` фильтрует по тому, кто написал код.

### 2. **Ограничение на 100 коммитов на репозиторий**
Код получает только первые 100 коммитов из каждого репозитория:

```php
// Строка 232 в github-api.php
$url = self::$api_url . '/repos/' . $username . '/' . $repo_name . '/commits?per_page=100&sort=author-date&order=desc&committer=' . $username;
```

**Проблема:** Если в репозитории больше 100 коммитов за период, остальные не учитываются.

### 3. **Пропуск коммитов из форков**
Код пропускает форки:

```php
// Строка 189-191 в github-api.php
if (!$repo['fork'] && !$repo['archived']) {
    $repos[] = array(
```

**Проблема:** Коммиты в форкнутых репозиториях не учитываются, хотя GitHub их показывает.

## Исправления:

### 1. Заменить `committer` на `author` и убрать ограничения
В файле `github-api.php` замените:

```php
// Было (строка ~232):
$url = self::$api_url . '/repos/' . $username . '/' . $repo_name . '/commits?per_page=100&sort=author-date&order=desc&committer=' . $username;

// Стало:
$url = self::$api_url . '/repos/' . $username . '/' . $repo_name . '/commits?per_page=100&sort=author-date&order=desc&author=' . $username;
```

### 2. Убрать ограничение на форки или сделать его опциональным
В файле `github-api.php` замените:

```php
// Было (строка ~189):
if (!$repo['fork'] && !$repo['archived']) {

// Стало (включаем форки):
if (!$repo['archived']) {
```

### 3. Добавить пагинацию для получения всех коммитов
Нужно модифицировать метод `get_repo_commits` для обработки пагинации:

```php
private static function get_repo_commits($username, $repo_name) {
    // ... существующий код проверки кэша ...

    $all_commits = array();
    $page = 1;
    $has_more = true;

    while ($has_more) {
        $url = self::$api_url . '/repos/' . $username . '/' . $repo_name . '/commits?per_page=100&page=' . $page . '&sort=author-date&order=desc&author=' . $username;

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

        // Если нет коммитов на странице или меньше 100, это последняя страница
        if (empty($data) || count($data) < 100) {
            $has_more = false;
        }

        foreach ($data as $commit) {
            $all_commits[] = array(
                'sha' => substr($commit['sha'], 0, 7),
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
    }

    // ... существующий код кэширования ...

    return $all_commits;
}
```

## Полный исправленный метод `get_repo_commits`:

```php
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
                    'sha' => substr($commit['sha'], 0, 7),
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
```

После этих изменений плагин будет:
1. Считать коммиты по автору, а не коммиттеру
2. Включать коммиты из форкнутых репозиториев
3. Обрабатывать все коммиты через пагинацию, а не только первые 100

Это должно устранить расхождения в подсчете коммитов между плагином и GitHub.