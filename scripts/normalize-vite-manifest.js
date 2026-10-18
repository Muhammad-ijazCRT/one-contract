import fs from 'node:fs';
import path from 'node:path';

const manifestPath = path.resolve('public/build/manifest.json');

if (!fs.existsSync(manifestPath)) {
    process.exit(0);
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const normalized = {};

for (const [key, value] of Object.entries(manifest)) {
    if (key.startsWith('_')) {
        normalized[key] = value;
        continue;
    }

    const resourceMatch = key.match(/resources[\\/].*$/);
    const normalizedKey = resourceMatch ? resourceMatch[0].replace(/\\/g, '/') : key.replace(/\\/g, '/');
    normalized[normalizedKey] = value;
}

fs.writeFileSync(manifestPath, JSON.stringify(normalized, null, 2));
