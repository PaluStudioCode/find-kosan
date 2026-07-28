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

    // 1. Remove useToast import
    content = content.replace(/import\s+\{\s*useToast\s*\}\s+from\s+['"]@\/components\/ui\/toast\/use-toast['"];?\n?/g, '');
    
    // 2. Remove const { toast } = useToast();
    content = content.replace(/const\s+\{\s*toast\s*\}\s*=\s*useToast\(\);?\n?/g, '');

    // 3. Add import { toast } from 'vue-sonner' if toast is still used and not already imported
    if (content.includes('toast') && !content.includes('vue-sonner')) {
        content = content.replace(/<script\s+setup>/, "<script setup>\nimport { toast } from 'vue-sonner';");
    }

    // 4. Replace toast({...}) with toast.success or toast.error
    content = content.replace(/toast\(\{\s*title:\s*['"](.*?)['"],\s*description:\s*(.*?),\s*variant:\s*['"]destructive['"]\s*\}\)/gs, (match, title, desc) => {
        return `toast.error(${desc})`;
    });

    content = content.replace(/toast\(\{\s*title:\s*['"](.*?)['"],\s*description:\s*(.*?)\s*\}\)/gs, (match, title, desc) => {
        return `toast.success(${desc})`;
    });

    if (content !== original) {
        fs.writeFileSync(filepath, content);
        console.log('Updated ' + filepath);
    }
});
