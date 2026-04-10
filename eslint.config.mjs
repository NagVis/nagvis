import js from "@eslint/js";
import globals from "globals";
import prettier from "eslint-config-prettier";

export default [
    {
        ignores: [
            "share/frontend/nagvis-js/js/Ext*.js"
        ]
    },
    js.configs.recommended,
    prettier,
    {
        languageOptions: {
            ecmaVersion: 2015,
            sourceType: "script",
            globals: {
                ...globals.browser
            }
        },
        rules: {
            // The JS codebase runs in a shared global browser scope across many files.
            // Variables defined in one file are freely referenced from others, so these
            // rules produce too many false positives without explicit per-file global
            // declarations. Enable them again once the codebase is modularised.
            "no-undef": "off",
            "no-unused-vars": "off"
        }
    }
];
