module.exports = {
    "sourceType": "unambiguous",
    "presets": [
        [
            "@babel/preset-env",
            {
                "modules": false,
                "targets": {},
                "useBuiltIns": "usage",
                "corejs": 3
            }
        ]
    ],
    "plugins": [
        "@babel/plugin-syntax-dynamic-import"
    ]
}
