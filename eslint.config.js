import js from '@eslint/js';
import vue from 'eslint-plugin-vue';
import vueTsConfigs from '@vue/eslint-config-typescript';
import eslintConfigPrettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
    js.configs.recommended,
    ...vue.configs['flat/recommended'],
    ...vueTsConfigs(),
    eslintConfigPrettier,
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
                route: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/block-lang': ['error', { script: { lang: 'ts' } }],
        },
    },
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/build/**',
            'bootstrap/cache/**',
            'resources/js/ziggy.js',
        ],
    },
];
