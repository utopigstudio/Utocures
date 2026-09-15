import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');

if (!existsSync(resolve(projectRoot, '.git'))) {
    process.exit(0);
}

let hooksPath = '';

try {
    hooksPath = execFileSync('git', ['config', '--get', 'core.hooksPath'], {
        cwd: projectRoot,
        encoding: 'utf8',
    }).trim();
} catch {
    // No custom hooks path is configured.
}

if (hooksPath === '.githooks') {
    execFileSync('git', ['config', '--unset', 'core.hooksPath'], {
        cwd: projectRoot,
        stdio: 'inherit',
    });
}

console.log('Local build hooks disabled. Builds run after pushes to main.');
