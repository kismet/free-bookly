const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

function getGitCommitId() {
  try {
    const commitId = execSync('git rev-parse --short HEAD', { encoding: 'utf8' }).trim();
    return commitId || 'dev';
  } catch (err) {
    return 'dev';
  }
}

function stampVersion() {
  const rootDir = path.resolve(__dirname, '..');
  const packageJsonPath = path.join(rootDir, 'package.json');
  const phpMainPath = path.join(rootDir, 'free-bookly.php');

  const pkg = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
  const baseVersion = pkg.version || '1.1.0';
  const commitId = getGitCommitId();
  const author = pkg.author || 'Stefano Lenzi';
  const fullVersion = `${baseVersion}-build.${commitId}`;

  // Aggiorna free-bookly.php
  let phpContent = fs.readFileSync(phpMainPath, 'utf8');

  // Sostituisce la versione nel docblock di intestazione di WordPress
  phpContent = phpContent.replace(/\*\s*Version:\s+[^\r\n]+/i, `* Version:           ${fullVersion}`);
  // Sostituisce l'autore nel docblock
  phpContent = phpContent.replace(/\*\s*Author:\s+[^\r\n]+/i, `* Author:            ${author}`);

  // Sostituisce o definisce le costanti PHP
  if (/define\(\s*'FREE_BOOKLY_BUILD'/.test(phpContent)) {
    phpContent = phpContent.replace(/define\(\s*'FREE_BOOKLY_VERSION',\s*'[^']+'\s*\);/, `define( 'FREE_BOOKLY_VERSION', '${baseVersion}' );`);
    phpContent = phpContent.replace(/define\(\s*'FREE_BOOKLY_BUILD',\s*'[^']+'\s*\);/, `define( 'FREE_BOOKLY_BUILD', '${commitId}' );`);
    phpContent = phpContent.replace(/define\(\s*'FREE_BOOKLY_VERSION_FULL',\s*'[^']+'\s*\);/, `define( 'FREE_BOOKLY_VERSION_FULL', '${fullVersion}' );`);
    phpContent = phpContent.replace(/define\(\s*'FREE_BOOKLY_AUTHOR',\s*'[^']+'\s*\);/, `define( 'FREE_BOOKLY_AUTHOR', '${author}' );`);
  } else {
    phpContent = phpContent.replace(
      /(define\(\s*'FREE_BOOKLY_VERSION',\s*'[^']+'\s*\);)/,
      `$1\ndefine( 'FREE_BOOKLY_BUILD', '${commitId}' );\ndefine( 'FREE_BOOKLY_VERSION_FULL', '${fullVersion}' );\ndefine( 'FREE_BOOKLY_AUTHOR', '${author}' );`
    );
    phpContent = phpContent.replace(/define\(\s*'FREE_BOOKLY_VERSION',\s*'[^']+'\s*\);/, `define( 'FREE_BOOKLY_VERSION', '${baseVersion}' );`);
  }

  fs.writeFileSync(phpMainPath, phpContent, 'utf8');

  console.log(`[Version Stamp] Free Bookly v${baseVersion} (build: ${commitId})`);
  console.log(`[Version Stamp] Full Version: ${fullVersion}`);
  console.log(`[Version Stamp] Author: ${author}`);

  return {
    baseVersion,
    commitId,
    fullVersion,
    author
  };
}

if (require.main === module) {
  stampVersion();
}

module.exports = { stampVersion, getGitCommitId };
