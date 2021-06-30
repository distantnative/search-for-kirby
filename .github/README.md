> **⏸ Development on hold**  
> Unfortunately, I am lacking time and energy to actively uphold development at the moment. With the release of Kirby 3.6, I want to settle the future of this plugin. Until then, use the plugin at your own risk - there are some known [issues](https://github.com/distantnative/search-for-kirby/issues). 
> 
> Apologies from my side, COVID takes its toll on me as well. If you want to help, please get in touch.
 <br>
 
 # Search for Kirby

[![Version](https://img.shields.io/badge/release-1.1.1-4271ae.svg?style=for-the-badge)](https://github.com/distantnative/search-for-kirby/releases)
[![Dependency](https://img.shields.io/badge/kirby-3.5.0-cca000.svg?style=for-the-badge)](https://getkirby.com/)
[![License](https://img.shields.io/badge/license-MIT-7ea328.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)
[![Donate](https://img.shields.io/badge/support-donate-c82829.svg?style=for-the-badge)](https://paypal.me/distantnative)

[![Screenshot](screenshot.jpg)](https://distantnative.com/search)

## Gettting started

### Installation

[Download](https://github.com/distantnative/search-for-kirby/archive/main.zip), unzip and copy this repository to `/site/plugins/search`.

Alternatively, you can install it with composer:

```
composer require distantnative/search-for-kirby
```

Decide which provider you want to use (see below for the available providers).
And don't forget to create the index (also below) before the first run, if using the Sqlite or Algolia provider.

### Updates
Replace the `/site/plugins/search` folder with the new version. Make sure to read the release notes for breaking changes.

Or if you installed the plugin via composer, run:

```
composer update distantnative/search-for-kirby
```

## Entries
The plugin offers a global search across different entries combined: `pages`, `files` and `users`.

You can define what should be included as entries in the index (using the Kirby [query language](https://getkirby.com/docs/guide/blueprints/query-language)) or even disable a type completely in your `site/config/config.php`:

```php
'search' => [
    'entries' => [
        'pages' => 'site.find("projects").index', 
        'files' => false,
        // users will remain the default 
    ]
]
```

By default the following collections are included in the index:

#### `pages`
```
site.index
```

#### `files`
```
site.index.files
```

#### `users`
```
kirby.users
```

## Fields
You can define in your `config.php` file which content fields of your pages, files and users to include in the search:

```php
'search' => [
    'fields' => [
        'pages' => [
            'title'
        ],
        'files' => [
            'filename'
        ],
        'users' => [
            'email',
            'name'
        ]
    ]
]
```

There are several options how to define fields in these arrays:

```php
// Simply add the field
'title',

// Add the field, but run it through a field method first
'text' => 'kirbytext',

// Pass parameters to the field method
'text' => ['short', 50],

// Use a callback function
'text' => function ($model) { 
    return strip_tags($model->text()->kt());
}

// Turn string field value into number (e.g. for Algolia filters)
'myNumberField' => function ($model) { 
    return $model->myNumberField()->toFloat();
}

'myVirtualNumberField' => function ($model) { 
    return $model->myNumberField()->toInt() + 5;
}

'myDate' => function ($model) { 
    return $model->anyDateField()->toTimestamp();
}
```

## Templates
You can also define in your `config.php` file which templates (or roles in the case of users) to include in the search:

```php
'search' => [
    'templates' => [
        'pages' => function ($model) {
             return $model->id() !== 'home' && $model->id() !== 'error';
         },
        'files' => null,
        'users' => null
    ]
]
``

If the value is null or an empty array, all templates are allowed. If it is false, all templates are excluded. There are several other options:

```php
// simple whitelist array
['project', 'note', 'album']

// associative array
[
    'project' => true,
    'note'    => false
]

// callback function
function ($model) {
    return $model->intendedTemplate() !== 'secret';
}
```

## Usage

### In the Panel
The plugin replaces the default Panel search (access at the top right via the magnifying glass icon) with its global search modal:

https://user-images.githubusercontent.com/3788865/124006273-53629680-d9da-11eb-9e4e-cd8e68f34a54.mp4

### On the frontend
The plugin also replaces the PHP API methods. Since the plugin provides global search, it is best to be used with the $site object as a start:

```php
$site->search('query', $options = []);
```

Nevertheless, search can also be limited to more specific collections (with some performance loss):

```php
$page->children()->listed()->filterBy('template', 'project')->search('query', $options = []);
```

The plugin also adds a global `search()` helper function:

```php
search(string $query, $options = [], $collection = null)
```

For the options array you can pass a limit option to sepcify the number of results to be returned. Moreover you can specify an operator option (`AND` or `OR`) to specify the rule multiple search terms get combined:

```php
collection('notes')->search($query, [
    'operator' => 'AND',
    'limit' => 100
]);
```

The result you receive is a [`Kirby\Cms\Collection` object](https://getkirby.com/docs/reference/objects/cms/collection).

## Providers
The plugin bundles three different search providers. Depending on your site, needs and setup, one of these might be more suitable than others.

### `sqlite` (default)
Creates an index SQLite database using the SQLite FTS5 extension (must be available).

In the config, you can redefine the location for the database file, providing an absolute path. By default the database file will be created as `site/logs/search/index.sqlite`.

```.php
// site/config/config.php

'search' => [
    'provider' => 'sqlite',
    'sqlite' => [
        'file'  => dirname(__DIR__, 2) . '/storage/search/index.sqlite'

    ]
]
```

The fuzzy option allows a search to match content not only from the beginning of words but also inside them. Depending on the content, it can drastically increase the size of the index database (think exponentially), especially when including long text fields. You can also define lists of fields to make fuzzy.

```php
// site/config/config.php

'search' => [
    'provider' => 'sqlite',
    'sqlite' => [
        // Enabled for all fields
        'fuzzy' => true,

        // Disabled completely
        'fuzzy' => false,

        // Only for selected fields
        'fuzzy' => [
            'pages' => ['title'],
            'files' => ['caption', 'credits']
        ],
    ]
]
```

You can also define some custom weights to rank specific fields higher than others:

```php
// site/config/config.php

'search' => [
    'provider' => 'sqlite',
    'sqlite' => [
        'weights' => [
            'title'   => 10,
            'caption' => 5
        ],
    ]
]
```

### `algolia`

Add Algolia search seamlessly to Kirby with this provider:

```php
// site/config/config.php

'search' => [
    'provider' => 'algolia',
    'algolia' => [
        'app'     => ...,     // Algolia App ID
        'key'     => ...,     // Algolia private admin key (not just read-only!)
        'index'   => 'kirby'  // name of the index to use/create
        'options' => []       // options to pass to Algolia at search
    ]
]
```

### Generating the index
#### Panel section
To create an initial index, the plugin bundles a small Panel section from which you can trigger building the index. Add the following section to e.g. your `site.yml` blueprint:

```yml
sections:
  search:
    type: search
```

#### Hooks
By default, the plugin makes sure to update the search index at each event (creating a page, uploading a file, updating content etc.) via hooks. So you do not need to worry to creating a new index each time content gets edited.

To deactivate automatic index updates via hooks, add the following to your `site/config/config.php`:

```php
'search' => [
  'hooks' => false
]
```

#### Shell script
You can also build the index from the command line with the `./index` shell script included in the plugin. This can be an alternative to the Panel section (especially for a very big index file) or replace hooks in setups that do not use the Panel – then triggered e.g. when deploying a new commit or via a cronjob.

```bash
./site/plugins/search/bin/index
```

If you are using a custom folder setup, you will have to create a modified version of that script. Get in touch, if you need help.

## Troubleshooting
This plugin is provided "as is" with no guarantees. Use it at your own risk and always test it yourself before using it in a production environment. If you encounter any problem, please [create an issue](https://github.com/distantnative/search-for-kirby/issues).

### Missing Sqlite extension
When generating the index, you might encounter the error could not find driver. This most likely means that your server setup is missing the Sqlite extensions. It has worked out for other users to activate the following extensions:

```
extension=sqlite3.so
extension=pdo_sqlite.so
```

## Pay it forward 💛
This plugin is completely free and published under the MIT license. However, development needs time and effort. If you are using it in a commercial project or just want to support me to keep this plugin alive, please [make a donation of your choice](https://paypal.me/distantnative).


