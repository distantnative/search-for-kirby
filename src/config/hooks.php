<?php

namespace Kirby\Search;

use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Cms\User;

$action = function () {
    $index = Index::instance();

    if (($index->options['hooks'] ?? true) === false) {
        return;
    }

    $args = func_get_args();
    $action = $args[0];
    $parameters = array_slice($args, 1);
    $index->$action(...$parameters);
};

return [
    'file.changeName:after' => function (File $newFile, File $oldFile) use ($action) {
        $action->call($this, 'update', $oldFile->id(), $newFile, 'files');
    },
    'file.create:after' => function (File $file) use ($action) {
        $action->call($this, 'insert', $file, 'files');
    },
    'file.delete:after' => function (File $file) use ($action) {
        $action->call($this, 'delete', $file, 'files');
    },
    'file.update:after' => function (File $newFile, File $oldFile) use ($action) {
        $action->call($this, 'update', $oldFile->id(), $newFile, 'files');
    },
    'page.changeSlug:after' => function (Page $newPage, Page $oldPage) use ($action) {
        $action->call($this, 'move', $oldPage, $newPage, 'pages');
    },
    'page.changeTemplate:after' => function (Page $newPage, Page $oldPage) use ($action) {
        $action->call($this, 'update', $oldPage->id(), $newPage, 'pages');
    },
    'page.changeTitle:after' => function (Page $newPage, Page $oldPage) use ($action) {
        $action->call($this, 'update', $oldPage->id(), $newPage, 'pages');
    },
    'page.create:after' => function (Page $page) use ($action) {
        $action->call($this, 'insert', $page, 'pages');
    },
    'page.delete:after' => function (Page $page) use ($action) {
        $action->call($this, 'delete', $page, 'pages');
    },
    'page.duplicate:after' => function (Page $page) use ($action) {
        $action->call($this, 'insert', $page, 'pages');
    },
    'page.update:after' => function (Page $newPage, Page $oldPage) use ($action) {
        $action->call($this, 'update', $oldPage->id(), $newPage, 'pages');
    },
    'site.update:after' => function (Site $newSite, Site $oldSite) use ($action) {
        $action->call($this, 'update', $oldSite->id(), $newSite, 'pages');
    },
    'user.changeEmail:after' => function (User $newUser, User $oldUser) use ($action) {
        $action->call($this, 'update', $oldUser->id(), $newUser, 'users');
    },
    'user.changeName:after' => function (User $newUser, User $oldUser) use ($action) {
        $action->call($this, 'update', $oldUser->id(), $newUser, 'users');
    },
    'user.changeRole:after' => function (User $newUser, User $oldUser) use ($action) {
        $action->call($this, 'move', $oldUser, $newUser, 'users');
    },
    'user.create:after' => function (User $user) use ($action) {
        $action->call($this, 'insert', $user, 'users');
    },
    'user.delete:after' => function (User $user) use ($action) {
        $action->call($this, 'delete', $user, 'users');
    },
    'user.update:after' => function (User $newUser, User $oldUser) use ($action) {
        $action->call($this, 'update', $oldUser->id(), $newUser, 'users');
    },
];
