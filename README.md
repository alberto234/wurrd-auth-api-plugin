# Mibew Button Refresh plugin

It make the button refresents actual operator's state.


## Installation

1. Get the archive with the plugin sources. At the moment the only option is to build the plugin from sources.

2. Untar/unzip the plugin's archive.

3. Put files of the plugins to the `<Mibew root>/plugins`  folder.

4. (optional) Add plugins configs to "plugins" structure in "`<Mibew root>`/configs/config.yml". If the "plugins" stucture looks like `plugins: []` it will become:
    ```yaml
    plugins:
        "Mibew:ButtonRefresh": # Plugin's configurations are described below
            refresh_frequency: 3
    ```

5. Navigate to "`<Mibew Base URL>`/operator/plugin" page and enable the plugin.

6. Regenerate button at "`<Mibew Base URL>`/operator/button-code" page.

Beware that when the plugin will be disabled or uninstalled the button MUST be regenerated.


## Plugin's configurations

The plugin can be configured with values in "`<Mibew root>`/configs/config.yml" file.

### config.refresh_frequency

Type: `Integer`

Default: `3`

Specify time in seconds between button refreshes. This value is optional and can be skipped.


## Build from sources

There are several actions one should do before use the latest version of the plugin from the repository:

1. Obtain a copy of the repository using `git clone`, download button, or another way.
2. Install [node.js](http://nodejs.org/) and [npm](https://www.npmjs.org/).
3. Install [Gulp](http://gulpjs.com/).
4. Install npm dependencies using `npm install`.
5. Run Gulp to build the sources using `gulp default`.

Finally `.tar.gz` and `.zip` archives of the ready-to-use Plugin will be available in `release` directory.


## License

[Apache License 2.0](http://www.apache.org/licenses/LICENSE-2.0.html)
