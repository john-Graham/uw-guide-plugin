
# Guide Content Importer for WordPress

## Overview

The Guide Content Importer is an experimental WordPress plugin designed to seamlessly import and parse content from guide.wisc.edu. It leverages Advanced Custom Fields (ACF) and Gutenberg blocks to facilitate content integration, providing a streamlined approach to enriching your WordPress site with external content.

## Prerequisites

Before you begin, ensure you have the following prerequisites installed:

- **WordPress**: The base platform for which this plugin is developed.
- **Advanced Custom Fields (ACF)**: This plugin requires ACF to function correctly. Make sure ACF is installed and activated on your WordPress site.
- **Node.js and npm**: The Node.js runtime and npm (Node Package Manager) are required for the development process, including building the project assets.

## Installation

1. **Clone or download the plugin**: Start by cloning this repository or downloading it to your local machine.
2. **Install npm dependencies**: Navigate to the plugin directory in your terminal and run the following command to install the necessary dependencies:

```bash
npm install
```

This will install all the dependencies defined in `package.json`.

## Building the Project

This project uses Gulp to automate the build process, including compiling and minifying CSS files for Gutenberg blocks.

- **Gulp Build**: To build the project, run the following command in the terminal:

```bash
gulp build
```

- **Watching for Changes**: The Gulp process is configured to watch the `blocks` folder for any changes. When a change is detected, Gulp will automatically build CSS files for each block. To start watching for changes, run:

```bash
gulp watch
```

## Usage

After installation and building the project, activate the plugin through the WordPress admin dashboard. Once activated, the Guide Content Importer will be ready to import and parse content from guide.wisc.edu into your WordPress site, utilizing ACF and Gutenberg blocks.

## Support

As this is an experimental plugin, and is posted in hopes we will get additional contributions.


