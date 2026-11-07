import {readFileSync, writeFileSync, mkdirSync} from 'node:fs';
import {dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {readPackage, checkInstalled} from './designer-package.mjs';

if (process.argv.length !== 3) throw new Error('Usage: node tests/import-designer.mjs <verified-designer.tgz>');
const {manifest, assets, license} = readPackage(readFileSync(process.argv[2]));
const root = new URL('../', import.meta.url);
// Validate every package entry before the first local source write.
for (const [name, bytes] of assets) {
    const target = new URL('Z2MAutomations/visualization/' + name, root);
    mkdirSync(dirname(fileURLToPath(target)), {recursive: true});
    writeFileSync(target, bytes);
}
writeFileSync(new URL('libs/designer-package.json', root), JSON.stringify(manifest, null, 4) + '\n');
writeFileSync(new URL('libs/designer-LICENSE', root), license);
checkInstalled();
console.log('Gemeinsames Designer-Paket geprüft und lokal übernommen: ' + manifest.version + ' / ' + manifest.content.slice(0, 12));
