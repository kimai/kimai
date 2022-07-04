module.exports = {
    env: {
        "browser": true,
        "node": true,
        "es6": true,
        "amd": true,
    },
    parser: '@babel/eslint-parser',
    extends: ['eslint:recommended'],
    ignorePatterns: ["assets/*.js"],
}