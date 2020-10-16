module.exports = {
  root: true,
  env: {
    node: true,
  },
  extends: [
    'plugin:vue/recommended',
    '@vue/airbnb',
  ],
  parserOptions: {
    parser: 'babel-eslint',
  },
  rules: {
    'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'import/extensions': 'off',
    'import/no-unresolved': 'off',
    'import/no-extraneous-dependencies': ['error', { devDependencies: true }],
    'no-restricted-syntax': ['off', 'ForOfStatement'],
    'no-param-reassign': ['error', { props: false }],
    'class-methods-use-this': 'off',
    'object-curly-newline': 'off',
    'no-nested-ternary': 'off',
    'no-await-in-loop': 'off',
    'max-len': 'off',
  },
};
