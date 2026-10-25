const fs = require('fs');
const archiver = require('archiver');
const path = require('path');

const output = fs.createWriteStream(__dirname + '/free-bookly.zip');
const archive = archiver('zip', {
  zlib: { level: 9 } // Massima compressione
});

output.on('close', function() {
  console.log('Build completata! file ' + archive.pointer() + ' bytes totali.');
  console.log('Il plugin è stato pacchettizzato con successo in free-bookly.zip e può essere installato in WordPress.');
});

archive.on('error', function(err) {
  throw err;
});

archive.pipe(output);

// Escludiamo cartelle e file non necessari per la produzione
const ignoreList = [
  'node_modules/**',
  'tests/**',
  'docs/*.md',
  'docs/test-report.pdf',
  '.git/**',
  '.gitignore',
  'package.json',
  'package-lock.json',
  'build.js',
  'generate-pdf.js',
  'test-e2e.js',
  'phpunit.xml.dist',
  'free-bookly.zip',
  'specifiche-plugin-booking.md'
];

archive.glob('**/*', {
  cwd: __dirname,
  ignore: ignoreList
}, { prefix: 'free-bookly' });

archive.finalize();
