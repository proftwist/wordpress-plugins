<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SG_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
    }

    /**
     * Регистрируем подменю в разделе "Настройки"
     */
    public function add_menu() {
        add_options_page(
            __( 'Smart Glossary Settings', 'smart-glossary' ), // Заголовок страницы
            __( 'Smart Glossary', 'smart-glossary' ),          // Текст в меню
            'manage_options',                                  // Права доступа
            'smart-glossary',                                  // Slug (идентификатор)
            array( $this, 'render_page' )                      // Функция вывода
        );
    }

    public function render_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . SG_TABLE_NAME;

        // Обработка действий (Сохранение, Удаление, Настройки)
        $this->handle_actions();

        $terms = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY term ASC" );
        $is_enabled = get_option( 'sg_enabled', '1' );

        // Получаем данные для редактирования, если есть
        $edit_term = null;
        $edit_id = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : 0;
        if ( $edit_id > 0 ) {
            $edit_term = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $edit_id ) );
        }

        ?>
        <div class="wrap">
            <h1><?php _e( 'Smart Glossary Settings', 'smart-glossary' ); ?></h1>

            <!-- Блок настроек -->
            <div class="card">
                <h2><?php _e( 'General Settings', 'smart-glossary' ); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'sg_save_settings', 'sg_settings_nonce' ); ?>
                    <label for="sg_enabled">
                        <input type="checkbox" name="sg_enabled" id="sg_enabled" value="1" <?php checked( $is_enabled, '1' ); ?>>
                        <?php _e( 'Enable Plugin', 'smart-glossary' ); ?>
                    </label>
                    <p class="submit">
                        <input type="submit" name="save_settings" class="button button-primary" value="<?php _e( 'Save Settings', 'smart-glossary' ); ?>">
                    </p>
                </form>
            </div>

            <hr>

            <!-- Форма добавления/редактирования -->
            <h2><?php echo $edit_term ? __( 'Edit Term', 'smart-glossary' ) : __( 'Add New Term', 'smart-glossary' ); ?></h2>
            <form method="post" action="" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                <?php wp_nonce_field( 'sg_save_term', 'sg_term_nonce' ); ?>
                <?php if ( $edit_term ) : ?>
                    <input type="hidden" name="term_id" value="<?php echo esc_attr( $edit_term->id ); ?>">
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="term"><?php _e( 'Term', 'smart-glossary' ); ?></label></th>
                        <td><input type="text" name="term" id="term" class="regular-text" value="<?php echo $edit_term ? esc_attr( $edit_term->term ) : ''; ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="definition"><?php _e( 'Definition', 'smart-glossary' ); ?></label></th>
                        <td><textarea name="definition" id="definition" rows="3" class="large-text" required><?php echo $edit_term ? esc_textarea( $edit_term->definition ) : ''; ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <?php if ( $edit_term ) : ?>
                        <input type="submit" name="update_term" class="button button-primary" value="<?php _e( 'Update Term', 'smart-glossary' ); ?>">
                        <a href="<?php echo admin_url( 'options-general.php?page=smart-glossary' ); ?>" class="button"><?php _e( 'Cancel', 'smart-glossary' ); ?></a>
                    <?php else : ?>
                        <input type="submit" name="add_term" class="button button-primary" value="<?php _e( 'Add Term', 'smart-glossary' ); ?>">
                    <?php endif; ?>
                </p>
            </form>

            <!-- Список терминов -->
            <h2><?php _e( 'Existing Terms', 'smart-glossary' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Term', 'smart-glossary' ); ?></th>
                        <th><?php _e( 'Definition', 'smart-glossary' ); ?></th>
                        <th style="width: 100px;"><?php _e( 'Actions', 'smart-glossary' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $terms ) ) : ?>
                        <?php foreach ( $terms as $term ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $term->term ); ?></strong></td>
                                <td><?php echo esc_html( $term->definition ); ?></td>
                                <td>
                                    <a href="<?php echo admin_url( 'options-general.php?page=smart-glossary&edit=' . $term->id ); ?>" class="button button-small">
                                        <?php _e( 'Edit', 'smart-glossary' ); ?>
                                    </a>
                                    <form method="post" action="" style="display:inline;">
                                        <?php wp_nonce_field( 'sg_delete_term', 'sg_delete_nonce' ); ?>
                                        <input type="hidden" name="term_id" value="<?php echo $term->id; ?>">
                                        <button type="submit" name="delete_term" class="button button-small button-link-delete" onclick="return confirm('<?php _e( 'Are you sure?', 'smart-glossary' ); ?>')">
                                            <?php _e( 'Delete', 'smart-glossary' ); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="3"><?php _e( 'No terms found.', 'smart-glossary' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function handle_actions() {
        global $wpdb;
        $table_name = $wpdb->prefix . SG_TABLE_NAME;

        // Сохранение настроек
        if ( isset( $_POST['save_settings'] ) && check_admin_referer( 'sg_save_settings', 'sg_settings_nonce' ) ) {
            $enabled = isset( $_POST['sg_enabled'] ) ? '1' : '0';
            update_option( 'sg_enabled', $enabled );
            echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Settings saved.', 'smart-glossary' ) . '</p></div>';
        }

        // Добавление термина
        if ( isset( $_POST['add_term'] ) && check_admin_referer( 'sg_save_term', 'sg_term_nonce' ) ) {
            $term = sanitize_text_field( $_POST['term'] );
            $def  = sanitize_textarea_field( $_POST['definition'] );

            if ( ! empty( $term ) && ! empty( $def ) ) {
                $wpdb->insert(
                    $table_name,
                    array( 'term' => $term, 'definition' => $def ),
                    array( '%s', '%s' )
                );
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Term added.', 'smart-glossary' ) . '</p></div>';
            }
        }

        // Обновление термина
        if ( isset( $_POST['update_term'] ) && check_admin_referer( 'sg_save_term', 'sg_term_nonce' ) ) {
            $id = intval( $_POST['term_id'] );
            $term = sanitize_text_field( $_POST['term'] );
            $def  = sanitize_textarea_field( $_POST['definition'] );

            if ( ! empty( $term ) && ! empty( $def ) && $id > 0 ) {
                $wpdb->update(
                    $table_name,
                    array( 'term' => $term, 'definition' => $def ),
                    array( 'id' => $id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Term updated.', 'smart-glossary' ) . '</p></div>';
            }
        }

        // Удаление термина
        if ( isset( $_POST['delete_term'] ) && check_admin_referer( 'sg_delete_term', 'sg_delete_nonce' ) ) {
            $id = intval( $_POST['term_id'] );
            $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
            echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Term deleted.', 'smart-glossary' ) . '</p></div>';
        }
    }
}
