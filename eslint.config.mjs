import js from "@eslint/js";
import globals from "globals";

export default [
    js.configs.recommended,
    {
        files: ["assets/js/**/*.js"],
        ignores: ["assets/*.js"],
        rules: {
            eqeqeq: 1,
            curly: "error",
            semi: ["warn", "always"],
        },
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
                ...globals.amd,
            }
        }
    },
];