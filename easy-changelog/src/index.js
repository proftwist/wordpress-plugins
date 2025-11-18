import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit';

registerBlockType('easy-changelog/changelog', {
    title: __('Easy Changelog', 'easy-changelog'),
    description: __('Display a beautiful changelog with JSON data', 'easy-changelog'),
    category: 'widgets',
    icon: 'list-view',
    supports: {
        html: false,
    },
    attributes: {
        changelogData: {
            type: 'string',
            default: `[
    {
        "version": "1.0.0",
        "date": "2024-01-15",
        "added": [
            "Initial release of the plugin",
            "Basic changelog functionality",
            "Gutenberg block integration"
        ]
    },
    {
        "version": "1.1.0",
        "date": "2024-01-20",
        "added": [
            "Added preview tab",
            "Improved styling",
            "JSON validation"
        ]
    }
]`
        }
    },
    edit: Edit,
    save: () => {
        return null; // Используем render_callback в PHP
    }
});