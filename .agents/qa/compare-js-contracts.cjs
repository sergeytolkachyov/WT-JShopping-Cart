const fs = require('node:fs');
const path = require('node:path');
const { createRequire } = require('node:module');
const assert = require('node:assert/strict');
const requireTool = createRequire(path.resolve(process.env.FRONTEND_QA_ROOT, 'package.json'));
const espree = requireTool('espree');

function clean(value) {
  if (!value || typeof value !== 'object') return value;
  if (Array.isArray(value)) return value.map(clean);
  return Object.fromEntries(Object.entries(value).filter(([key]) => !['start', 'end', 'loc', 'range', 'raw'].includes(key)).map(([key, v]) => [key, clean(v)]));
}
function inventory(file) {
  const ast = espree.parse(fs.readFileSync(file, 'utf8'), { ecmaVersion: 2015, sourceType: 'script' });
  const result = { publicMethods: [], requests: [], domCalls: [], htmlAssignments: [] };
  function walk(node) {
    if (!node || typeof node !== 'object') return;
    if (Array.isArray(node)) { node.forEach(walk); return; }
    if (node.type === 'AssignmentExpression' && node.left.type === 'MemberExpression') {
      if (node.left.object.name === 'wtJshopModuleCart' && node.right.type === 'ArrowFunctionExpression') {
        result.publicMethods.push({ name: node.left.property.name, params: clean(node.right.params) });
      }
      if (node.left.property.name === 'innerHTML') result.htmlAssignments.push(clean(node.left));
    }
    if (node.type === 'CallExpression' && node.callee.type === 'MemberExpression') {
      if (node.callee.object.name === 'Joomla' && node.callee.property.name === 'request') {
        result.requests.push(clean(node.arguments[0].properties.filter(property => ['url', 'method'].includes(property.key.name))));
      }
      if (node.callee.object.name === 'document') result.domCalls.push(clean(node));
    }
    Object.values(node).forEach(walk);
  }
  walk(ast);
  return result;
}
const before = inventory(process.argv[2]);
const after = inventory(path.resolve(__dirname, '../../mod_wtjshoppingcart/media/js/wtjshoppingcart.js'));
assert.deepEqual(after, before);
console.log(JSON.stringify({ publicMethods: after.publicMethods.length, requests: after.requests.length, existingDOMCalls: after.domCalls.length, htmlAssignments: after.htmlAssignments.length, contractsUnchanged: true }));
