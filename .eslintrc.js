module.exports = {
    env: {
        "browser": true,
        "node": true,
        "es6": true,
        "amd": true,
    },
    rules: {
        "eqeqeq": 1,
        "curly": "error",
    },
    parser: '@babel/eslint-parser',
    extends: ['eslint:recommended'],
    ignorePatterns: ["assets/*.js"],
}