const fs = require('fs');
const archiver = require('archiver');
const path = require('path');
const { getVersionInfo, getStampedPhpContent } = require('./scripts/version');

const versionInfo = getVersionInfo();
const stampedPhp = getStampedPhpContent();

const zipPath = path.join(__dirname, 'free-bookly.zip');
const output = fs.createWriteStream(zipPath);
const archive = archiver('zip', {
  zlib: { level: 9 } // Massima compressione per la distribuzione
});

output.on('close', function() {
  const sizeKb = Math.round(archive.pointer() / 1024);
  console.log('====================================================');
  console.log(`[BUILD SUCCESS] Free Bookly v${versionInfo.fullVersion}`);
  console.log(`Autore: ${versionInfo.author}`);
  console.log(`Dimensione archivio: ${archive.pointer()} bytes (~${sizeKb} KB)`);
  console.log('File generato con successo: free-bookly.zip');
  console.log('Il pacchetto è pronto per essere installato su qualsiasi istanza WordPress.');
  console.log('====================================================');
});

archive.on('error', function(err) {
  console.error('[BUILD ERROR]', err);
  throw err;
});

archive.pipe(output);

// Inseriamo free-bookly.php con il timbro esatto di versione Git
archive.append(stampedPhp, { name: 'free-bookly/free-bookly.php' });

// Escludiamo cartelle e file non necessari per l'ambiente di produzione
const ignoreList = [
  'free-bookly.php', // Inserito manualmente con archive.append sopra
  'node_modules/**',
  'scripts/**',
  'tests/**',
  'docs/*.md',
  'docs/test-report.pdf',
  '.git/**',
  '.gitignore',
  'package.json',
  'package-lock.json',
  'build.js',
  'generate-pdf.js',
  'test-*.js',
  '.tmp-*/**',
  'phpunit.xml.dist',
  'free-bookly.zip',
  'specifiche-plugin-booking.md'
];

archive.glob('**/*', {
  cwd: __dirname,
  ignore: ignoreList
}, { prefix: 'free-bookly' });

archive.finalize();
