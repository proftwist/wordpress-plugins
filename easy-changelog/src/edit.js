import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
    PanelBody,
    TextareaControl,
    TabPanel,
    Notice
} from '@wordpress/components';
import {
    InspectorControls,
    useBlockProps
} from '@wordpress/block-editor';

const Edit = ({ attributes, setAttributes }) => {
    const { changelogData } = attributes;
    const [jsonError, setJsonError] = useState('');
    const [previewData, setPreviewData] = useState([]);
    const blockProps = useBlockProps();

    // Валидация и парсинг JSON
    useEffect(() => {
        try {
            if (changelogData.trim()) {
                const parsed = JSON.parse(changelogData);
                if (Array.isArray(parsed)) {
                    setPreviewData(parsed);
                    setJsonError('');
                } else {
                    setJsonError(__('JSON must be an array', 'easy-changelog'));
                }
            } else {
                setPreviewData([]);
                setJsonError('');
            }
        } catch (error) {
            setJsonError(__('Invalid JSON format', 'easy-changelog'));
            setPreviewData([]);
        }
    }, [changelogData]);

    const tabs = [
        {
            name: 'json',
            title: __('JSON Editor', 'easy-changelog'),
            className: 'easy-changelog-json-tab',
        },
        {
            name: 'preview',
            title: __('Preview', 'easy-changelog'),
            className: 'easy-changelog-preview-tab',
        },
    ];

    const renderJsonTab = () => (
        <div className="easy-changelog-json-editor">
            {jsonError && (
                <Notice status="error" isDismissible={false}>
                    {jsonError}
                </Notice>
            )}
            <TextareaControl
                label={__('Changelog JSON', 'easy-changelog')}
                help={__('Enter valid JSON array with version, date, and added fields', 'easy-changelog')}
                value={changelogData}
                onChange={(value) => setAttributes({ changelogData: value })}
                rows={20}
                className="easy-changelog-textarea"
            />
        </div>
    );

    const renderPreviewTab = () => (
        <div className="easy-changelog-preview">
            {jsonError ? (
                <Notice status="warning" isDismissible={false}>
                    {__('Fix JSON errors to see preview', 'easy-changelog')}
                </Notice>
            ) : previewData.length === 0 ? (
                <Notice status="info" isDismissible={false}>
                    {__('No changelog data to display', 'easy-changelog')}
                </Notice>
            ) : (
                <div className="easy-changelog-block">
                    <h3 className="easy-changelog-title">{__('Changelog', 'easy-changelog')}</h3>
                    <div className="easy-changelog-list">
                        {previewData.map((release, index) => (
                            <div key={index} className="easy-changelog-release">
                                <div className="easy-changelog-header">
                                    <span className="easy-changelog-version">
                                        {release.version || 'No version'}
                                    </span>
                                    <span className="easy-changelog-date">
                                        {release.date || 'No date'}
                                    </span>
                                </div>
                                {release.added && release.added.length > 0 && (
                                    <div className="easy-changelog-section">
                                        <h4 className="easy-changelog-section-title">
                                            {__('Added', 'easy-changelog')}
                                        </h4>
                                        <ul className="easy-changelog-items">
                                            {release.added.map((item, itemIndex) => (
                                                <li key={itemIndex} className="easy-changelog-item">
                                                    {item}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Changelog Settings', 'easy-changelog')} initialOpen={true}>
                    <p>{__('Use the JSON Editor tab to input your changelog data in JSON format.', 'easy-changelog')}</p>
                </PanelBody>
            </InspectorControls>

            <div className="easy-changelog-editor">
                <TabPanel
                    className="easy-changelog-tabs"
                    activeClass="is-active"
                    onSelect={() => {}}
                    tabs={tabs}
                >
                    {(tab) => (
                        <div className="easy-changelog-tab-content">
                            {tab.name === 'json' && renderJsonTab()}
                            {tab.name === 'preview' && renderPreviewTab()}
                        </div>
                    )}
                </TabPanel>
            </div>
        </div>
    );
};

export default Edit;