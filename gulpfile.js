
const { src, dest, series, parallel, watch } = require('gulp');
const { rm } = require('fs/promises');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');
const postcss = require('gulp-postcss');
const rename = require('gulp-rename');
const sass = require('gulp-sass')(require('sass'));
const sourcemaps = require('gulp-sourcemaps');

const paths = {
    "src": "./assets/src",
    "dist": "./assets/dist",
    "blocks": './blocks',
    "nodeModules": "./node_modules"
};

const filePath = {
    "styles": {
        "src": paths.src + "/scss",
        "dist": paths.dist + "/css",
        "nodeModules": "./node_modules"
    }
};




function globalCss() {
    let plugins = [
        autoprefixer,
        cssnano
    ];
    console.log(filePath.styles.src + '/*.scss')
    return src(filePath.styles.src + '/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass.sync({ includePaths: [paths.nodeModules] }).on('error', sass.logError))
        .pipe(dest(filePath.styles.dist))
        .pipe(rename({ suffix: '.min' }))
        .pipe(postcss(plugins))
        .pipe(sourcemaps.write('./'))
        .pipe(dest(filePath.styles.dist));
}
function blockCss() {
    let plugins = [
        autoprefixer,
        cssnano
    ];
    return src(paths.blocks + '/**/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass.sync({ includePaths: [paths.nodeModules] }).on('error', sass.logError))
        .pipe(dest(paths.blocks))
        .pipe(rename({suffix: '.min'}))
        .pipe(postcss(plugins))
        .pipe(sourcemaps.write('./'))
        .pipe(dest(paths.blocks));
}




let compileAllCss = series(
    globalCss,
    blockCss,
);


function fileWatch() {
    watch([filePath.styles.src + '/**/*.scss', paths.blocks + '/**/*.scss'], { interval: 1000 }, compileAllCss);

}



function clean() {
    return rm(paths.dist, { recursive: true, force: true });
}


let build = series(
    clean,
    compileAllCss,
);


exports.build = build; // Run `gulp build` to run production build
exports.blockCss = blockCss;
exports.clean = clean; // Run `gulp clean` to empty dist folder
exports.css = compileAllCss;
exports.default = series(build, parallel(fileWatch));
exports.watch = fileWatch; // Run `gulp watch` to watch for changes