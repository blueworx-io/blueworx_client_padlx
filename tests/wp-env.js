// Starts or stops the disposable test site with Foundation's script.
// Foundation is expected next to this repo; set FOUNDATION_DIR if it is somewhere else.
// Usage: node tests/wp-env.js up|down [extra options, e.g. --port 8894]

const path = require('path');
const { spawnSync } = require('child_process');

const foundation = process.env.FOUNDATION_DIR || path.join(__dirname, '..', '..', 'bluegroup_core_foundation');
const [command, ...rest] = process.argv.slice(2);
const args = [path.join(foundation, 'scripts', 'wp-test-env.mjs'), command];
if (command === 'up') {
  args.push('--plugin', path.join(__dirname, '..'));
}
const result = spawnSync(process.execPath, [...args, ...rest], { stdio: 'inherit' });
process.exit(result.status === null ? 1 : result.status);
