// Runs once after the suite. Removes the test-only mu-plugin, so the test site
// behaves like the real plugin again for anyone checking it by hand.

const fs = require('fs');
const path = require('path');

module.exports = async () => {
  const file = path.join(__dirname, '..', '.wp-test', 'wp', 'wp-content', 'mu-plugins', 'padlx-test-harness.php');
  fs.rmSync(file, { force: true });
};
