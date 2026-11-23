"""Check PHP style and PHPDoc without changing application behavior."""
import argparse
import json
import os
from pathlib import Path
import subprocess

parser = argparse.ArgumentParser()
parser.add_argument('--baseline', help='Optional original source directory for PHP AST comparison.')
parser.add_argument('--js', action='store_true', help='Also check the cart JavaScript and run its tests.')
parser.add_argument('--report-dir', type=Path, help='Optional output directory for reports.')
args = parser.parse_args()
root = Path(__file__).resolve().parents[2]
module_root = root / 'mod_wtjshoppingcart'
plugin_root = root / 'plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart'
os.chdir(root)
env = os.environ.copy()
binding = root / '.agents/qa/local.properties'
if binding.exists():
    for line in binding.read_text(encoding='utf-8').splitlines():
        if '=' in line and line.split('=', 1)[0] in ('PHP_QA_ROOT', 'FRONTEND_QA_ROOT'):
            key, value = line.split('=', 1)
            env.setdefault(key, value.strip())
if not env.get('PHP_QA_ROOT'):
    raise SystemExit('Set PHP_QA_ROOT or its value in .agents/qa/local.properties.')
runtime = args.report_dir or root / '.pf/runtime/joomla-code-style-fixes'
runtime.mkdir(parents=True, exist_ok=True)
bin_dir = Path(env['PHP_QA_ROOT']) / 'vendor/bin'
results = []

def run(name, command):
    result = subprocess.run([str(arg) for arg in command], env=env, capture_output=True, text=True, encoding='utf-8', errors='replace')
    (runtime / (name + '.stdout.txt')).write_text(result.stdout, encoding='utf-8')
    (runtime / (name + '.stderr.txt')).write_text(result.stderr, encoding='utf-8')
    results.append({'check': name, 'exit_code': result.returncode})
    print(name + ': ' + ('passed' if result.returncode == 0 else 'FAILED'), flush=True)
    if result.returncode: print(result.stdout, result.stderr)

php_files = sorted([
    *module_root.glob('src/**/*.php'),
    *module_root.glob('services/**/*.php'),
    *module_root.glob('tmpl/*.php'),
    *module_root.glob('layouts/**/*.php'),
    root / 'pkg_script.php',
    *plugin_root.glob('src/**/*.php'),
    *plugin_root.glob('services/**/*.php'),
])
for file in php_files:
    run('syntax-' + file.relative_to(root).as_posix().replace('/', '-'), ['php', '-l', file])
run('phpcs', ['php', bin_dir / 'phpcs', '--standard=.agents/qa/phpcs.xml', '--extensions=php', '--report=json', *php_files])
run('fixer', ['php', bin_dir / 'php-cs-fixer', 'fix', '--dry-run', '--config=.agents/qa/php-cs-fixer.php', '--using-cache=no', '--format=json'])
run('phpdoc', ['php', '.agents/qa/verify-phpdoc.php'])
if args.baseline:
    run('ast-comparison', ['php', '.agents/qa/compare-php-ast.php', args.baseline])
if args.js:
    if not env.get('FRONTEND_QA_ROOT'):
        raise SystemExit('Set FRONTEND_QA_ROOT to the shared frontend QA runtime.')
    run('eslint', ['node', Path(env['FRONTEND_QA_ROOT']) / 'node_modules/eslint/bin/eslint.js', '--config', '.agents/qa/eslint.config.mjs', '--format', 'json', module_root / 'media/js', plugin_root / 'media/js'])
    run('cart-math', ['node', '--test', '.agents/qa/tests/cart-math.test.cjs'])
    run('add-to-cart', ['node', '--test', '.agents/qa/tests/add-to-cart.test.cjs'])
(runtime / 'results.json').write_text(json.dumps(results, indent=2), encoding='utf-8')
raise SystemExit(0 if all(result['exit_code'] == 0 for result in results) else 1)
