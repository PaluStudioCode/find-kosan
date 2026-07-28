const fs = require('fs');
const path = require('path');

function walkSync(dir, callback) {
    fs.readdirSync(dir).forEach(file => {
        const filepath = path.join(dir, file);
        if (fs.statSync(filepath).isDirectory()) {
            walkSync(filepath, callback);
        } else if (filepath.endsWith('.vue')) {
            callback(filepath);
        }
    });
}

walkSync('resources/js/Pages', (filepath) => {
    let content = fs.readFileSync(filepath, 'utf8');
    let original = content;

    // Fix trailing }); from regex mistake
    content = content.replace(/toast\.error\((.*?)\s*\}\);?/g, 'toast.error($1);');
    content = content.replace(/toast\.success\((.*?)\s*\}\);?/g, 'toast.success($1);');

    // Also swap success and error because my regex logic might have been flawed
    content = content.replace(/toast\.error\((['"](.*?)berhasil(.*?)['"])\)/gi, 'toast.success($1)');
    content = content.replace(/toast\.success\((['"](.*?)gagal(.*?)['"])\)/gi, 'toast.error($1)');
    content = content.replace(/toast\.success\((['"](.*?)Periksa(.*?)['"])\)/gi, 'toast.error($1)');

    if (content !== original) {
        fs.writeFileSync(filepath, content);
        console.log('Fixed', filepath);
    }
});
