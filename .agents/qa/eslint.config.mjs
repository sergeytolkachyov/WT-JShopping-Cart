import { createRequire } from 'node:module';
import path from 'node:path';

const require = createRequire(path.resolve(process.env.FRONTEND_QA_ROOT || '.', 'package.json'));
const js = require('@eslint/js');
const globals = require('globals');

export default [{
  files: ['mod_wtjshoppingcart/media/js/**/*.js', 'plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart/media/js/**/*.js'],
  languageOptions: {
    ecmaVersion: 2015,
    sourceType: 'script',
    globals: { ...globals.browser, Joomla: 'readonly', wtJshopModuleCart: 'readonly' },
  },
  rules: {
    ...js.configs.recommended.rules,
    'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
  },
}];
