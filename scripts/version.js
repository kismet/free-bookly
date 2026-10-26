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

function getVersionInfo() {
  const rootDir = path.resolve(__dirname, '..');
  const packageJsonPath = path.join(rootDir, 'package.json');
  const pkg = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));

  const baseVersion = pkg.version || '1.1.0';
  const commitId = getGitCommitId();
  const author = pkg.author || 'Stefano Lenzi';
  const fullVersion = `${baseVersion}-build.${commitId}`;

  return {
    rootDir,
    baseVersion,
    commitId,
    fullVersion,
    author
  };
}

function getStampedPhpContent() {
  const { rootDir, baseVersion, commitId, fullVersion, author } = getVersionInfo();
  const phpMainPath = path.join(rootDir, 'free-bookly.php');
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

  return phpContent;
}

function stampVersion(writeToDisk = true) {
  const info = getVersionInfo();
  const stampedContent = getStampedPhpContent();

  if (writeToDisk) {
    const phpMainPath = path.join(info.rootDir, 'free-bookly.php');
    fs.writeFileSync(phpMainPath, stampedContent, 'utf8');
  }

  console.log(`[Version Stamp] Free Bookly v${info.baseVersion} (build: ${info.commitId})`);
  console.log(`[Version Stamp] Full Version: ${info.fullVersion}`);
  console.log(`[Version Stamp] Author: ${info.author}`);

  return info;
}

if (require.main === module) {
  stampVersion(true);
}

module.exports = {
  getVersionInfo,
  getStampedPhpContent,
  stampVersion,
  getGitCommitId
};
