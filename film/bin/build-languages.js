const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🔄 Компиляция переводов для блока Фотоплёнка...');

try {
    // Создаем папку languages если её нет
    const languagesDir = path.join(__dirname, '..', 'languages');
    if (!fs.existsSync(languagesDir)) {
        fs.mkdirSync(languagesDir, { recursive: true });
    }

    // Создаем POT файл
    console.log('📝 Создание POT файла...');
    execSync('npx wp i18n make-pot . languages/film.pot --include="src,build" --exclude="node_modules"', {
        cwd: path.join(__dirname, '..'),
        stdio: 'inherit'
    });

    // Компилируем JSON файлы из PO
    console.log('🔧 Компиляция JSON файлов...');
    execSync('npx wp i18n make-json languages --pretty-print', {
        cwd: path.join(__dirname, '..'),
        stdio: 'inherit'
    });

    console.log('✅ Переводы скомпилированы успешно!');
} catch (error) {
    console.error('❌ Ошибка компиляции переводов:', error);
    process.exit(1);
}