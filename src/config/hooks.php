<?php
namespace Kirby\Search;

function runHook() {
    $index = Index::instance();

    if (($index->options['hooks'] ?? true) === false) {
        return;
    }

    $args = func_get_args();
    $action = $args[0];
    $parameters = array_slice($args, 1);
    call_user_func_array([$index, $action], $parameters);
}

return [
    'file.changeName:after' => function ($newFile, $oldFile) {
        runHook('update', $oldFile->id(), $newFile, 'files');
    },
    'file.create:after' => function ($file) {
        runHook('insert', $file, 'files');
    },
    'file.delete:after' => function ($status, $file) {
        runHook('delete', $file, 'files');
    },
    'file.update:after' => function ($newFile, $oldFile) {
        runHook('update', $oldFile->id(), $newFile, 'files');
    },
    'page.changeSlug:after' => function ($newPage, $oldPage) {
        runHook('move', $oldPage, $newPage, 'pages');
    },
    'page.changeTemplate:after' => function ($newPage, $oldPage) {
        runHook('update', $oldPage->id(), $newPage, 'pages');
    },
    'page.changeTitle:after' => function ($newPage, $oldPage) {
        runHook('update', $oldPage->id(), $newPage, 'pages');
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
        runHook('update', $oldPage->id(), $newPage, 'pages');
    },
    'site.update:after' => function ($newSite, $oldSite) {
        runHook('update', $oldSite->id(), $newSite, 'pages');
    },
    'user.changeEmail:after' => function ($newUser, $oldUser) {
        runHook('update', $oldUser->id(), $newUser, 'users');
    },
    'user.changeName:after' => function ($newUser, $oldUser) {
        runHook('update', $oldUser->id(), $newUser, 'users');
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
        runHook('update', $oldUser->id(), $newUser, 'users');
    },
];
