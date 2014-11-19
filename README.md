# Lock - Acl for PHP 5.4+

[![Build Status](https://img.shields.io/travis/BeatSwitch/lock/master.svg?style=flat-square)](https://travis-ci.org/BeatSwitch/lock)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Packagist Version](https://img.shields.io/packagist/v/beatswitch/lock.svg?style=flat-square)](https://packagist.org/packages/beatswitch/lock)
[![Total Downloads](https://img.shields.io/packagist/dt/beatswitch/lock.svg?style=flat-square)](https://packagist.org/packages/beatswitch/lock)

<img width="100%" src="https://s3.eu-central-1.amazonaws.com/assets.beatswitch.com/lock_banner.png">

Lock is a flexible, driver based **Acl** package for **PHP 5.4+**.

<a href="http://creativeskills.be/jobs/php-development/php-developer-beatswitch-14-10-14.html">Job Details</a>. Logo by <a href="http://www.jerrylow.com">Jerry Low</a>.

> **Warning:** This package is currently in a pre-alpha stage. This means that there is no guaranty that the current structure, contracts, terminology an/or implementations will stay the same until the package hits stable. Please do not use this in production until a stable release it out.

## Table of Contents

- [Terminology](#terminology)
- [Introduction](#introduction)
- [Drivers](#drivers)
- [Roadmap](#roadmap)
- [Installation](#installation)
- [Usage](#usage)
    - [Implementing the Caller contract](#implementing-the-caller-contract)
    - [Working with a static driver](#working-with-a-static-driver)
    - [Working with a persistent driver](#working-with-a-persistent-driver)
    - [Setting a God caller](#setting-a-god-caller)
- [Api](#api)
- [Building a Driver](#building-a-driver)
- [Maintainer](#maintainer)
- [License](#license)

## Terminology

- `Caller`: On object that can have permissions to do something
- `Driver`: A storage system for permissions which can either be static or persistent
- `Permission`: A permission holds an action and an optional (unique) resource. Can be either a `Restriction` or a `Privilege`
- `Restriction`: A restriction denies you from being able to perform an action on an (optional) resource
- `Privilege`: A privilege allows you to perform an action on an (optional) resource
- `Action`: An action is something you are either allowed or denied to do
- `Resource`: A resource can be an object where you can perform one or more actions on. It can either target a certain type of resources or a specific resource by its unique identifier

## Introduction

Lock differs from other Acl packages by trying to provide the most flexible way for working with multiple permission callers and storing permissions.

By working with Lock's `Caller` contract you can set permissions on multiple identities.

The `Driver` contract allows for an easy way to store permissions to a persistent or static storage system. A default static `ArrayDriver` ships with this package. Check out the list below for more drivers which have already been prepared for you. Or build your own by implementing the `Driver` contract.

You can set and check permissions for resources by manually passing along a resource's type and (optional) identifier or you can implement the `Resource` contract onto your objects so you can pass them along to lock more easily.

## Drivers

If you need a framework-specific implementation, pick one of the already prepared drivers below.

- ArrayDriver (ships with this package)
- Laravel (coming soon)

## Roadmap

- Action Aliases
- Permission Conditions
- Roles
- Group Permissions
- More drivers (Symfony, Zend Framework, Doctrine, ...)
- Event Listeners

## Installation

Install this package through Composer.

```bash
composer require beatswitch/lock
```

## Usage

### Implementing the Caller contract

Every identity which should have permissions to do something must implement the `Caller` contract. The `Caller` contract identifies a caller by requiring it to return its type and its unique identifier. Let's look at an example below.

```php
<?php

use BeatSwitch\Lock\Contracts\Caller;

class User implements Caller
{
    public function getCallerType()
    {
        return 'users';
    }

    public function getCallerId()
    {
        return $this->id;
    }
}
```

By adding the `getCallerType` function we can identify a group of callers through a unique type. If we would at some point wanted to set permissions on another group of callers we could easily implement the contract on another object.

```php
<?php

use BeatSwitch\Lock\Contracts\Caller;

class Organization implements Caller
{
    public function getCallerType()
    {
        return 'organizations';
    }

    public function getCallerId()
    {
        return $this->id;
    }
}
```

And thus we can easily retrieve permissions for a specific caller type through a persistent storage driver.

### Working with a static driver

If you'd like to configure all of your permissions beforehand you can use the static `ArrayDriver` which ships with the package. This allows you to set a list of permissions for a caller at runtime.

```php
use \BeatSwitch\Lock\Drivers\ArrayDriver;
use \BeatSwitch\Lock\Lock;

// Instantiate the Lock instance with the static ArrayDriver.
$lock = new Lock($caller, new ArrayDriver());

// Set some permissions.
$lock->allow('manage_settings');
$lock->allow('create', 'events');

// Use the Lock instance to validate permissions on the given caller.
$lock->can('manage_settings'); // true: can manage settings
$lock->can('create', 'events'); // true: can create events
$lock->cannot('update', 'events'); // true: cannot update events
$lock->can('delete', 'events'); // false: cannot delete events
```

### Working with a persistent driver

Working with **a persistent driver** allows you to store permissions to a persistent storage layer and adjust them during runtime. For example, if you'd implement the Laravel driver, it would store the permissions to a database using Laravel's database component. By creating your own UI, you could easily attach the acl functionality from this package to create, for example, a user management system where different users have different permissions.

Let's take a look at a very basic user management controller to see how that's done. We'll assume we get a bootstrapped lock manager instance with our Laravel DB driver.

```php
<?php

use BeatSwitch\Lock\Manager;

class UserManagementController extends BaseController
{
    protected $lockManager;

    public function __construct(Manager $lockManager)
    {
        $this->lockManager = $lockManager;
    }

    public function togglePermission()
    {
        $userId = Input::get('user');
        $action = Input::get('action');
        $resource = Input::get('resource');

        $user = User::find($userId);

        $this->lockManager->caller($user)->toggle($action, $resource);

        return Redirect::route('user_management');
    }
}
```

Every time the `togglePermission` method is used, the user's permission for the given action and resource type will be toggled.

### Setting a God caller

You could easily set a caller which has all permissions for everything by passing the `all` wildcard as an action on the lock instance.

```php
use \BeatSwitch\Lock\Drivers\ArrayDriver;
use \BeatSwitch\Lock\Lock;

// Instantiate the Lock instance.
$lock = new Lock($caller, new ArrayDriver());

// Set a single permission with the `all` wildcard.
$lock->allow('all');
```

Now every "can" method call will validate to true for this caller.

## Api

The following methods can all be called on a valid `Lock` instance.

`can(string $action, string|Resource $resource = null, int $resourceId = null)`

Checks to see if the current caller has permission to do something.

`cannot(string $action, string|Resource $resource = null, int $resourceId = null)`

Checks to see if it's forbidden for the current caller to do something.

`allow(string $action, string|Resource $resource = null, int $resourceId = null)`

Sets a `Privilege` permission on a caller to do something. Removes any matching restrictions.

`deny(string $action, string|Resource $resource = null, int $resourceId = null)`

Sets a `Restriction` permission on a caller to do something. Removes any matching privileges.

`toggle(string $action, string|Resource $resource = null, int $resourceId = null)`

Toggles the return value for the given permission.

## Building a Driver

You can easily build a driver by implementing the `BeatSwitch\Lock\Contracts\Driver` contract. Below we'll demonstrate how to create our own persistent driver using Laravel's Eloquent ORM as our storage mechanism. We'll assume we have a `Permission` model class with at least the following database columns:

- `caller_type` (varchar, 100)
- `caller_id` (int, 11)
- `type` (varchar, 10)
- `action` (varchar, 100)
- `resource_type` (varchar, 100)
- `resource_id` (int, 11)

Let's check out a full implementation of the driver below. Notice that for the `getPermissions` method we're using the `PermissionFactory` class to easily map the data and create `Permission` objects from them.

```php
<?php

use Permission;
use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\Contracts\Driver;
use BeatSwitch\Lock\Contracts\Permission as PermissionContract;
use BeatSwitch\Lock\Permissions\PermissionFactory;

class EloquentDriver implements Driver
{
    /**
     * Returns all the permissions for a caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @return \BeatSwitch\Lock\Contracts\PermissionContract[]
     */
    public function getPermissions(Caller $caller)
    {
        $permissions = Permission::all();
        
        return PermissionFactory::createFromArray($permissions);
    }

    /**
     * Stores a new permission into the driver for a caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    public function storePermission(Caller $caller, PermissionContract $permission)
    {
        $permission = new Permission;
        $permission->caller_type = $caller->getCallerType();
        $permission->caller_id = $caller->getCallerId();
        $permission->type = $permission->getType();
        $permission->action = $permission->getAction();
        $permission->resource_type = $permission->getResource();
        $permission->resource_id = $permission->getResourceId();
        $permission->save();
    }

    /**
     * Removes a permission from the driver for a caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    public function removePermission(Caller $caller, PermissionContract $permission)
    {
        Permission::where('caller_type', $caller->getCallerType())
            ->where('caller_id', $caller->getCallerId())
            ->where('type', $permission->getType())
            ->where('action', $permission->getAction())
            ->where('resource_type', $permission->getResource())
            ->where('resource_id', $permission->getResourceId())
            ->delete();
    }

    /**
     * Checks if a permission is stored for a user
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return bool
     */
    public function hasPermission(Caller $caller, PermissionContract $permission)
    {
        return (bool) Permission::where('caller_type', $caller->getCallerType())
            ->where('caller_id', $caller->getCallerId())
            ->where('type', $permission->getType())
            ->where('action', $permission->getAction())
            ->where('resource_type', $permission->getResource())
            ->where('resource_id', $permission->getResourceId())
            ->first();
    }
}
```

Notice that we're not checking if the permission already exists when we're attempting to store it. You don't need to worry about that because that's all been done for you in the `Lock` instance.

## Maintainer

This package is currently maintained by [Dries Vints](https://github.com/driesvints).
If you have any questions please don't hesitate to ask them in an issue.

## License

The MIT License (MIT). Please see [the License File](LICENSE.md) for more information.