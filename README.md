# Auth API plugin for Mibew by Wurrd

This plugin provides authentication APIs for Mibew, and it is loosely based on OAuth2, specifically the Client Credentials (and Refresh Token) grant type. The ultimate goal will be for this plugin to strictly adhere to OAuth2, even if this means utilizing an OAuth2 library for PHP under the hood if that is what makes more sense. 

There are currently two APIs for authentication:

1. An HTTP API for external applications
2. A PHP API that is exposed for use by other plugins

## HTTP API

See comments in routing.yml

## PHP API 

See comments in Classes/AccessManagerAPI.php

## Diagrams

A state diagram can be found in [docs/Wurrd_Authorization_API_State_Diagram.pdf](https://github.com/alberto234/wurrd-auth-api-plugin/blob/master/docs/Wurrd_Authorization_API_State_Diagram.pdf)

## Installation

1. Get the built archive for this plugin from [here](http://wurrdapp.com/get-it-now).
2. Untar/unzip the plugin's archive.
3. Put files of the plugins to the `<Mibew root>/plugins`  folder.
4. Add plugins configs to "plugins" structure in "`<Mibew root>`/configs/config.yml". If the "plugins" stucture looks like `plugins: []` it will become:
    ```yaml
    plugins:
        "Wurrd:AuthAPI": # Plugin's configurations are described below
     		client_id: TEST_CLIENT_ID
   ```
Note: The configuration hierarchy is built through by parsing the indentation of the config.yml file, so the number of spaces before each line matters. See [issue 2](https://github.com/alberto234/wurrd-auth-api-plugin/issues/2) for symptoms of a bad config.yml file.

5. Navigate to "`<Mibew Base URL>`/operator/plugin" page and enable the plugin.


## Plugin's configurations

The plugin can be configured with values in "`<Mibew root>`/configs/config.yml" file.

### config.client_id

Type: `String`

This is a [list of] client id(s) that are allowed to access the system. Still to be impemented.
The TEST_CLIENT_ID above is a place holder that should be included. However, this functionality has not been implemented yet


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
