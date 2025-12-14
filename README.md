# Create a static site bundle from a Laravel app

![voltra/laravel-export](https://raw.githubusercontent.com/Voltra/laravel-export/main/art/voltra__laravel_export.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/voltra/laravel-export.svg?style=flat-square)](https://packagist.org/packages/voltra/laravel-export)
[![Total Downloads](https://img.shields.io/packagist/dt/oltra/laravel-export.svg?style=flat-square)](https://packagist.org/packages/voltra/laravel-export)

```
$ php artisan export
Exporting site...
Files were saved to disk `export`
```

Build your blog or site with Laravel like with the tools you're used to having and export it to be hosted statically.

Laravel Export will scan your app and create an HTML page from every URL it crawls. The entire `public` directory also gets added to the bundle so your assets are in place too.

A few example use cases for this package:

- Build your own blog or site in Laravel with all the tools you're used to using. Export a static version and just upload it anywhere for hosting, no need for managing a full-blown server anymore.

- Use something like [Nova](https://nova.laravel.com/), [Wink](https://github.com/themsaid/wink), [Filament](https://filamentphp.com/), [Sharp](https://sharp.code16.fr/), or any other admin panel to manage your site locally or on a remote server, then publish it to a service like Netlify. This gives you all benefits of a static site (speed, simple hosting, scalability) while still having a dynamic backend of some sort.

## Why a fork?

> I don't want to maintain this complexity in our package

This is an answer I received to a PR that fixed symlink detection on Windows but also on all other OS. Without that PR, directories like the one symlinked via `php artisan storage:link` were not properly exported.

If you don't count comments and unit tests, the PR was a 10 lines edit (including proper indentation and multiline function calls).

As such `voltra/laravel-export` can be treated as a community-driven fork of `spatie/laravel-export` that won't shy away from bugfixes.

It's even treated as a replacement for that package on a composer level. That means that if you use other packages that depend on `spatie/laravel-export` and you then choose to require `voltra/laravel-export`, then that's what every package will be using.

Such a fork will also allow to slightly diverge from upstream to allow nice QoL features like on-export base URL replacement (WIP).

## Compatibility

The `voltra/laravel-export` version range on the right can replace the version range of `spatie/laravel-export` on the left.

| spatie/laravel-export | voltra/laravel-export |
|:---------------------:|:---------------------:|
|     ^1.0 <=1.2.1      |         ^1.0 <= 1.2.3         |

## Installation

You can install the package via composer:

```bash
composer require voltra/laravel-export
```

After the package is installed, you can optionally publish the config file.

```bash
php artisan vendor:publish --provider=Spatie\\Export\\ExportServiceProvider
```

## Configuration

Laravel Export doesn't require configuration to get started, but there are a few things you can tweak to your needs.

```php
// config/export.php

return [
    'disk' => 'export',
];
```

This means you can also use other filesystem drivers, so you could export your site straight to something like S3.

```dotenv
# Optional, if the env variable is not defined it'll not do any replacement
EXPORT_BASE_URL="https://my.base-url.com/prefix"
```

### (optional) Vite plugin

If you heavily use JS in your app, you might want to register the included vite plugin to properly handle rewrites and absolute URLs in your app:

```javascript
// vite.config.js
// [...]
import laravelExport from "./vendor/voltra/laravel-export/resources/js/vite-laravel-export-plugin";
// [...]

export default defineConfig({
    // [...]
    laravel({
        // [...]
    }),
    laravelExport(),
    // [...]
})
```

Then you can resolve URLs in your app using the helper function:

```javascript
import { asExportUrl } from "/my/path/to/vendor/laravel-export/resources/js/vite-laravel-export-plugin"

const url = asExportUrl("/test"); // URL object
console.log(url);
console.log(url.toString());
```

### Determining the export contents

#### Crawling

With the default configuration, Laravel Export will crawl your site and export every page to a static site. If you'd like to disable this behaviour, disable the `crawl` option.

```php
return [
    'crawl' => true,
];
```

#### Paths

`paths` is an array of URL paths that will be exported to HTML. Use this to manually determine which pages should be exported.

```php
return [
    'paths' => [
        '/',
        '/rss.xml',
    ],
];
```

#### Including files

`include_files` allows you to specify files and folders relative to the application root that should be added to the export. By default, we'll include the entire `public` folder.

```php
return [
    'include_files' => [
        'public' => '',
    ],
];
```

`exclude_file_patterns` will check all source paths of included files, and exclude them if they match a pattern from in `exclude_file_patterns`. By default, all PHP files will be excluded, mainly to stop `index.php` from appearing in your export. Because the `mix-manifest.json` is no longer needed after compilation it is also excluded by default.

```php
return [
    'exclude_file_patterns' => [
        '/\.php$/',
        '/mix-manifest\.json$/',
    ],
];
```

#### Configuration through code

All configuration options that affect the exports contents are also exposed in the `Exporter` class. You can inject this class to modify the export settings through code.

```php
use Illuminate\Support\ServiceProvider;
use Spatie\Export\Exporter;

class AppServiceProvider extends ServiceProvider
{
    public function boot(Exporter $exporter)
    {
        $exporter->crawl(false);

        $exporter->paths(['', 'about', 'contact', 'posts']);
        $exporter->paths(Post::all()->pluck('slug'));
    }
}
```

### Custom disks

By default, Laravel Export will save the static bundle in a `dist` folder in your application root. If you want to store the site in a different folder, [configure a new disk](https://laravel.com/docs/5.8/filesystem) in `config/filesystem.php`.

```php
// config/filesystem.php

return [
    'disks' => [
        //

        'export' => [
            'driver' => 'local',
            'root' => base_path('out'),
        ],
    ],
];
```

### Hooks

`before` and `after` hooks allow you to do things before or after an export. Hooks can contain any shell command.

The default configuration doesn't have any hooks configured, but shows two examples.

With this `before` hook, we'll use Yarn to build our assets before every export:

```php
return [
    'before' => [
        'assets' => '/usr/local/bin/yarn production',
    ],
];
```

With this `after` hook, we'll deploy the static bundle to Netlify with their [CLI tool](https://www.netlify.com/docs/cli/) after the export.

```php
return [
    'after' => [
        'deploy' => '/usr/local/bin/netlify deploy --prod',
    ],
];
```

If you want to run an export without certain hooks, use `--skip-{hook}` flags.

```bash
php artisan export --skip-deploy
```

To skip before, after and all hooks use the `--skip-before`, `--skip-after`, `--skip-all` flags respectively.

```bash
php artisan export --skip-before
```

```bash
php artisan export --skip-after
```

```bash
php artisan export --skip-all
```

## Usage

To build a bundle, run the `export` command:

```bash
php artisan export
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

If you've found a bug regarding security please mail [dev@ludwig-guerin.fr](mailto:dev@ludwig-guerin.fr) instead of using the issue tracker.

## Credits

- [Voltra](https://github.com/Voltra)
- [Sebastian De Deyne](https://github.com/sebastiandedeyne)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
