const fs = require('fs');
const path = require('path');

console.log('🔍 Проверка переводов...');

const pluginPath = path.join(__dirname, '..');
const languagesPath = path.join(pluginPath, 'languages');
const buildPath = path.join(pluginPath, 'build');

// Проверяем существование папок
console.log('📁 Languages path:', languagesPath);
console.log('📁 Build path:', buildPath);

// Проверяем JSON файлы
const jsonFiles = fs.readdirSync(languagesPath).filter(f => f.endsWith('.json'));
console.log('📄 JSON files:', jsonFiles);

jsonFiles.forEach(file => {
    const filePath = path.join(languagesPath, file);
    const content = fs.readFileSync(filePath, 'utf8');
    try {
        const json = JSON.parse(content);
        console.log(`✅ ${file}: VALID (${Object.keys(json.locale_data?.messages || {}).length} переводов)`);
    } catch (e) {
        console.log(`❌ ${file}: INVALID - ${e.message}`);
    }
});

// Проверяем asset файл
const assetFile = path.join(buildPath, 'index.asset.php');
if (fs.existsSync(assetFile)) {
    console.log('✅ Asset file exists');
    const asset = require(assetFile);
    console.log('📦 Dependencies:', asset.dependencies);
    console.log('🔢 Version:', asset.version);
} else {
    console.log('❌ Asset file missing');
}