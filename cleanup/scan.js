const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const root = path.resolve(process.argv[2] || path.join(__dirname, '..'));
const outDir = path.join(__dirname);
const now = new Date().toISOString().replace(/[:.]/g, '_');
const reportJson = path.join(outDir, `scan_report_${now}.json`);
const reportTxt = path.join(outDir, `scan_report_${now}.txt`);
const patterns = ['.bak','.old','.new','.sql','.sql.gz','.log','.tmp','.temp','~','Thumbs.db','.DS_Store'];
const excludeExt = ['.php','.js','.css','.html','.md','.json','.yml','.yaml'];

function walk(dir, filelist=[]) {
  const files = fs.readdirSync(dir);
  for (const f of files) {
    const full = path.join(dir,f);
    let stat;
    try { stat = fs.statSync(full); } catch (e) { continue }
    if (stat.isDirectory()) {
      if (f === 'node_modules' || f === '.git') continue;
      walk(full, filelist);
    } else if (stat.isFile()) filelist.push({path: full, size: stat.size, mtime: stat.mtime});
  }
  return filelist;
}

function isPatternCandidate(file) {
  for (const p of patterns) if (file.path.endsWith(p)) return true;
  return false;
}
function isExcluded(file) {
  for (const e of excludeExt) if (file.path.endsWith(e)) return true;
  return false;
}

const all = walk(root);
const patternMatches = all.filter(f => isPatternCandidate(f) && !isExcluded(f));
const largeMatches = all.filter(f => f.size > 5 * 1024 * 1024);

// duplicates by quick hash for <=50MB
const smallFiles = all.filter(f => f.size <= 50 * 1024 * 1024);
const hashMap = {};
for (const f of smallFiles) {
  try {
    const buf = fs.readFileSync(f.path);
    const h = crypto.createHash('sha256').update(buf).digest('hex');
    if (!hashMap[h]) hashMap[h] = [];
    hashMap[h].push(f);
  } catch(e) { }
}
const duplicates = Object.values(hashMap).filter(g => g.length > 1);

const report = {root, generated: new Date().toISOString(), patternMatches, largeMatches, duplicates};
fs.writeFileSync(reportJson, JSON.stringify(report, null, 2));

let txt = `Scan report for ${root}\nGenerated: ${new Date().toISOString()}\n\n`;
txt += `-- Pattern matches (${patternMatches.length}) --\n`;
for (const f of patternMatches) txt += `${f.path}  [${(f.size/1024/1024).toFixed(2)} MB]\n`;
txt += `\n-- Large files (${largeMatches.length}) --\n`;
for (const f of largeMatches) txt += `${f.path}  [${(f.size/1024/1024).toFixed(2)} MB]\n`;
txt += `\n-- Duplicate groups (${duplicates.length}) --\n`;
for (const g of duplicates) {
  txt += `Group:\n`;
  for (const f of g) txt += `  - ${f.path} [${(f.size/1024/1024).toFixed(4)} MB]\n`;
  txt += `\n`;
}

fs.writeFileSync(reportTxt, txt);
console.log('Reports written:', reportJson, reportTxt);
