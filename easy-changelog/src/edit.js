import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';
import { TextareaControl, TabPanel } from '@wordpress/components';

// Получаем переводы из PHP
const i18n = window.easyChangelogI18n || {};

/**
 * Компонент редактирования блока Easy Changelog
 *
 * @param {Object} attributes - Атрибуты блока
 * @param {Function} setAttributes - Функция для обновления атрибутов
 */
const Edit = ({ attributes, setAttributes }) => {
    // Получаем данные changelog из атрибутов
    const { changelogData } = attributes;

    // Состояния компонента
    const [previewData, setPreviewData] = useState([]); // Данные для предпросмотра
    const [jsonError, setJsonError] = useState('');      // Ошибки парсинга JSON

    /**
     * Эффект для валидации и парсинга JSON данных
     * Выполняется при изменении changelogData
     */
    useEffect(() => {
        try {
            // Пытаемся распарсить JSON
            const parsed = JSON.parse(changelogData);

            // Проверяем, что данные являются массивом
            if (Array.isArray(parsed)) {
                setPreviewData(parsed);     // Устанавливаем данные для предпросмотра
                setJsonError('');           // Очищаем ошибки
            } else {
                // Если данные не массив, показываем ошибку
                setJsonError(i18n.mustBeArray || __('Данные должны быть массивом', 'easy-changelog'));
            }
        } catch (error) {
            // При ошибке парсинга JSON показываем сообщение об ошибке
            setJsonError(i18n.invalidJson || __('Некорректный JSON формат', 'easy-changelog'));
        }
    }, [changelogData]);

    /**
     * Конфигурация вкладок редактора
     */
    const tabs = [
        {
            name: 'editor',
            title: i18n.jsonEditor || __('Редактор JSON', 'easy-changelog'),
        },
        {
            name: 'preview',
            title: i18n.preview || __('Предпросмотр', 'easy-changelog'),
        },
    ];

    /**
     * Рендер вкладки редактора JSON
     */
    const renderEditorTab = () => (
        <div>
            <TextareaControl
                // Метка поля
                label={i18n.changelogData || __('Данные Changelog (JSON)', 'easy-changelog')}

                // Значение поля
                value={changelogData}

                // Обработчик изменения
                onChange={(value) => setAttributes({ changelogData: value })}

                // Количество строк в поле
                rows={15}

                // Подсказка под полем
                help={i18n.jsonHelp || __('Введите данные в формате JSON. Каждый релиз должен содержать version, date и added.', 'easy-changelog')}
            />

            {/* Отображение ошибок парсинга */}
            {jsonError && (
                <div style={{ color: '#cc1818', marginTop: '10px' }}>
                    {i18n.error || __('Ошибка:', 'easy-changelog')} {jsonError}
                </div>
            )}
        </div>
    );

    /**
     * Рендер вкладки предпросмотра
     */
    const renderPreviewTab = () => (
        <div className="easy-changelog-preview">
            {/* Если есть ошибки, показываем сообщение */}
            {jsonError ? (
                <div style={{ color: '#cc1818' }}>
                    {i18n.cannotPreview || __('Невозможно отобразить предпросмотр из-за ошибок в JSON', 'easy-changelog')}
                </div>
            ) : (
                <>
                    {/* Заголовок блока */}
                    <h2 className="easy-changelog-title">
                        {i18n.changelogTitle || __('История изменений', 'easy-changelog')}
                    </h2>

                    {/* Отображение релизов */}
                    {previewData.map((release, index) => (
                        <div key={index} className="easy-changelog-release">
                            {/* Версия релиза */}
                            <div className="easy-changelog-version">
                                <strong>{release.version}</strong>
                            </div>

                            {/* Дата релиза */}
                            <div className="easy-changelog-date">
                                {release.date}
                            </div>

                            {/* Список изменений */}
                            <div className="easy-changelog-added">
                                {Array.isArray(release.added) && (
                                    <ul>
                                        {release.added.map((item, itemIndex) => (
                                            <li key={itemIndex}>{item}</li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                    ))}
                </>
            )}
        </div>
    );

    /**
     * Основной рендер компонента
     */
    return (
        <div {...useBlockProps()}>
            <TabPanel
                className="easy-changelog-tabs"
                tabs={tabs}
            >
                {(tab) => (
                    <div className="easy-changelog-tab-content">
                        {tab.name === 'editor' && renderEditorTab()}
                        {tab.name === 'preview' && renderPreviewTab()}
                    </div>
                )}
            </TabPanel>
        </div>
    );
};

export default Edit;