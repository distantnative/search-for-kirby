<?php
namespace Kirby\Algolia;

function runHook() {
    if (option('getkirby.algolia.config')['hooks'] === false) {
        return;
    }

    $args = func_get_args();
    $action = $args[0];
    $parameters = array_slice($args, 1);
    call_user_func_array([Index::instance(), $action], $parameters);
}

return [
    'file.changeName:after' => function ($newFile, $oldFile) {
        runHook('update', $newFile, 'files');
    },
    'file.create:after' => function ($file) {
        runHook('insert', $file, 'files');
    },
    'file.delete:after' => function ($status, $file) {
        runHook('delete', $file, 'files');
    },
    'file.update:after' => function ($newFile, $oldFile) {
        runHook('update', $newFile, 'files');
    },
    'page.changeSlug:after' => function ($newPage, $oldPage) {
        runHook('move', $oldPage, $newPage, 'pages');
    },
    'page.changeTemplate:after' => function ($newPage, $oldPage) {
        runHook('update', $newPage, 'pages');
    },
    'page.changeTitle:after' => function ($newPage, $oldPage) {
        runHook('update', $newPage, 'pages');
    },
    'page.create:after' => function ($page) {
        runHook('insert', $page, 'pages');
    },
    'page.delete:after' => function ($status, $page) {
        runHook('delete', $page, 'pages');
    },
    'page.duplicate:after' => function ($page) {
        runHook('insert', $page, 'pages');
    },
    'page.update:after' => function ($newPage, $oldPage) {
        runHook('update', $newPage, 'pages');
    },
    'site.update:after' => function ($newSite, $oldSite) {
        runHook('update', $newSite, 'pages');
    },
    'user.changeEmail:after' => function ($newUser, $oldUser) {
        runHook('update', $newUser, 'users');
    },
    'user.changeName:after' => function ($newUser, $oldUser) {
        runHook('update', $newUser, 'users');
    },
    'user.changeRole:after' => function ($newUser, $oldUser) {
        runHook('move', $oldUser, $newUser, 'users');
    },
    'user.create:after' => function ($user) {
        runHook('insert', $user, 'users');
    },
    'user.delete:after' => function ($status, $user) {
        runHook('delete', $user, 'users');
    },
    'user.update:after' => function ($newUser, $oldUser) {
        runHook('update', $newUser, 'users');
    },
];
