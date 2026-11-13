import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**', 'storage/**', 'bootstrap/ssr/**'],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
            },
        },
        rules: {
            'vue/html-indent': ['error', 4],
            'vue/max-attributes-per-line': ['warn', { singleline: 4 }],
            // Page files are named after their route (auth/login), not as multi-word components.
            'vue/multi-word-component-names': ['error', { ignores: ['index', 'login', 'show'] }],
        },
    },
    {
        // Tests define throwaway host components inline.
        files: ['**/*.test.js'],
        rules: {
            'vue/one-component-per-file': 'off',
        },
    },
    {
        files: ['*.config.js'],
        languageOptions: {
            globals: {
                ...globals.node,
            },
        },
    },
];
