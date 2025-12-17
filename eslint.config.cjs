module.exports = [
  {
    ignores: ["node_modules/**", "vendor/**", "uploads/**"],
    languageOptions: {
      ecmaVersion: 2021,
      sourceType: "module",
      globals: {
        window: "readonly",
        document: "readonly",
        navigator: "readonly",
        console: "readonly",
        localStorage: "readonly",
        FileReader: "readonly",
        Chart: "readonly",
        bootstrap: "readonly"
      }
    },
    rules: {
      "no-console": "off",
      "no-unused-vars": ["warn", {"args": "none", "ignoreRestSiblings": true}],
      "eqeqeq": ["error", "always"]
    }
  }
];
