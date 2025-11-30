<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SG_Frontend {

    private $terms_cache = null;

    public function __construct() {
        add_filter( 'the_content', array( $this, 'auto_link_terms' ), 20 );
    }

    /**
     * Получает термины из БД, сортируя их по длине (от длинных к коротким),
     * чтобы избежать частичных совпадений внутри фраз.
     */
    private function get_terms() {
        if ( $this->terms_cache !== null ) {
            return $this->terms_cache;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . SG_TABLE_NAME;

        // Получаем все термины
        $results = $wpdb->get_results( "SELECT term, definition FROM $table_name" );

        // Сортируем по длине строки (DESC), чтобы "WordPress Plugin" заменялся раньше, чем "WordPress"
        usort( $results, function( $a, $b ) {
            return mb_strlen( $b->term ) - mb_strlen( $a->term );
        });

        $this->terms_cache = $results;
        return $this->terms_cache;
    }

    public function auto_link_terms( $content ) {
        // Если плагин выключен или это не основной запрос (опционально), возвращаем контент
        if ( get_option( 'sg_enabled', '1' ) !== '1' ) {
            return $content;
        }

        $terms = $this->get_terms();
        if ( empty( $terms ) ) {
            return $content;
        }

        // Создаем мапу термин -> определение для быстрого доступа
        $term_map = array();
        foreach ( $terms as $t ) {
            // Экранируем термин для использования в регулярном выражении
            $term_map[ $t->term ] = esc_attr( $t->definition );
        }

        // Собираем все термины в одну группу регулярного выражения: (Term1|Term2|Long Term)
        // preg_quote экранирует спецсимволы, чтобы они не ломали regex
        $escaped_terms = array_map( function($t) { return preg_quote($t->term, '/'); }, $terms );
        $pattern_terms = implode( '|', $escaped_terms );

        if ( empty( $pattern_terms ) ) {
            return $content;
        }

        /*
         * Регулярное выражение:
         * 1. (<a\b[^>]*>.*?<\/a>|<[^>]+>) - Находит готовые ссылки или любые HTML теги.
         * 2. \b($pattern_terms)\b - Находит наши термины (только целые слова).
         * Флаги: u (utf-8), i (case insensitive), s (dotall)
         */
        $regex = '/(<a\b[^>]*>.*?<\/a>|<[^>]+>)|(\b(?:' . $pattern_terms . ')\b)/uis';

        $content = preg_replace_callback( $regex, function( $matches ) use ( $term_map ) {
            // Если найдена группа 1 (HTML тег или ссылка), возвращаем без изменений
            if ( ! empty( $matches[1] ) ) {
                return $matches[1];
            }

            // Иначе найдена группа 2 (наш термин)
            $found_term = $matches[2];

            // Находим оригинальное определение (учитывая регистронезависимый поиск, нужно найти ключ в мапе)
            // Так как ключи в $term_map чувствительны к регистру, пройдемся и найдем правильный definition
            // Это не супер быстро, но надежно. Для оптимизации можно ключи хранить в lowercase.
            $definition = '';
            foreach ( $term_map as $t_key => $t_def ) {
                if ( mb_strtolower( $t_key ) === mb_strtolower( $found_term ) ) {
                    $definition = $t_def;
                    break;
                }
            }

            return sprintf( '<abbr class="smart-glossary-term" title="%s">%s</abbr>', $definition, $found_term );

        }, $content );

        return $content;
    }
}
