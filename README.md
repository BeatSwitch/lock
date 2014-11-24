# Lock - Acl for PHP 5.4+

[![Build Status](https://img.shields.io/travis/BeatSwitch/lock/master.svg?style=flat-square)](https://travis-ci.org/BeatSwitch/lock)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Packagist Version](https://img.shields.io/packagist/v/beatswitch/lock.svg?style=flat-square)](https://packagist.org/packages/beatswitch/lock)
[![Total Downloads](https://img.shields.io/packagist/dt/beatswitch/lock.svg?style=flat-square)](https://packagist.org/packages/beatswitch/lock)

<img width="100%" src="https://s3.eu-central-1.amazonaws.com/assets.beatswitch.com/lock_banner.png">

Lock is a flexible, driver based **Acl** package for **PHP 5.4+**.

Made possible thanks to [BeatSwitch](https://beatswitch.com). Inspired by [Authority](https://github.com/machuga/authority) by [Matthew Machuga](https://twitter.com/machuga). Logo by [Jerry Low](http://www.jerrylow.com).

[Come work with us at BeatSwitch!](http://creativeskills.be/jobs/php-development/php-developer-beatswitch-14-10-14.html)

> **Warning:** This package is currently in alpha stage. This means that there is no guaranty that the current structure, contracts, terminology an/or implementations will stay the same until the package hits stable. Please do not use this in production until a stable release it out.

## Table of Contents

- [Terminology](#terminology)
- [Features](#features)
- [Introduction](#introduction)
- [Drivers](#drivers)
- [Roadmap](#roadmap)
- [Installation](#installation)
- [Usage](#usage)
    - [Implementing the Caller contract](#implementing-the-caller-contract)
    - [Working with a static driver](#working-with-a-static-driver)
    - [Working with a persistent driver](#working-with-a-persistent-driver)
    - [Setting and checking permissions](#setting-and-checking-permissions)
    - [Setting an action alias](#setting-an-action-alias)
    - [Setting a God caller](#setting-a-god-caller)
    - [Working with roles](#working-with-roles)
    - [Working with conditions](#working-with-conditions)
    - [Using the LockAware trait](#using-the-lockaware-trait)
- [Api](#api)
- [Building a Driver](#building-a-driver)
- [Maintainer](#maintainer)
- [License](#license)

## Terminology

- `Caller`: An identity object that can have permissions to do something
- `Driver`: A storage system for permissions which can either be static or persistent
- `Permission`: A permission holds an action and an optional (unique) resource. Can be either a `Restriction` or a `Privilege`
- `Restriction`: A restriction denies you from being able to perform an action (on an optional resource)
- `Privilege`: A privilege allows you to perform an action (on an optional resource)
- `Action`: An action is something you are either allowed or denied to do
- `Resource`: A resource can be an object where you can perform one or more actions on. It can either target a certain type of resource or a specific resource by its unique identifier

## Features

- Flexible Acl permissions for multiple identities (callers)
- Static or persistent drivers to store permissions
- Action aliases
- Roles
- Conditions (Asserts)
- Easily Implement acl functionality on your caller with a trait

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

    public function getCallerRoles()
    {
        return ['editor', 'publisher'];
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

    public function getCallerRoles()
    {
        return ['enterprise'];
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

### Setting and checking permissions

You can either `allow` or `deny` a caller from doing something. Here are a couple of ways to set and check permissions.

Allow a caller to create everything.

```php
$lock->allow('create');

$lock->can('create'); // true
```

Allow a caller to only create posts.

```php
$lock->allow('create', 'posts');

$lock->can('create'); // false
$lock->can('create', 'posts'); // true
```

Allow a caller to only edit a specific post with an ID of 5.

```php
$lock->allow('edit', 'posts', 5);

$lock->can('edit'); // false
$lock->can('edit', 'posts'); // false
$lock->can('edit', 'posts', 5); // true
```

Allow a caller to edit all posts but deny them from editing one with the id of 5.

```php
$lock->allow('edit', 'posts');
$lock->deny('edit', 'posts', 5);

$lock->can('edit', 'posts'); // true
$lock->can('edit', 'posts', 5); // false
```

Toggle a permission's value.

```php
$lock->allow('create');
$lock->can('create'); // true

$lock->toggle('create');
$lock->can('create'); // false
```

You can allow or deny multiple actions at once and also check multiple actions at once.

```php
$lock->allow(['create', 'edit'], 'posts');

$lock->can('create', 'posts'); // true
$lock->can(['create', 'edit'], 'posts'); // true
$lock->can(['create', 'delete'], 'posts'); // false
```

### Setting an action alias

To group multiple actions and set them all at once you might want to set an action alias.

```php
$lock->alias('manage', ['create', 'read', 'delete']);
$lock->allow('manage', 'posts');

$lock->can('manage', 'posts'); // true
$lock->can('create', 'posts'); // true
$lock->can('delete', 'posts', 1); // true
$lock->can('update', 'posts'); // false
```

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

### Working with roles

Lock provides an easy way to working with roles. Before you get started with adding permissions to roles, you first need to register them on a lock instance.

```php
$lock->setRole('guest');
$lock->setRole('user', 'guest'); // "user" will inherit all permissions from "guest"
```

Or register multiple roles at once.

```php
$lock->setRole(['editor', 'admin'], 'user'); // "editor" and "admin" will inherit all permissions from "user".
```

Let's set some permissions and see how they are resolved.

```php
// Allow a guest to read everything.
$lock->allowRole('guest', 'read');

// Allow a user to create posts.
$lock->allowRole('user', 'create', 'posts');

// Allow an editor and admin to publish posts.
$lock->allowRole(['editor', 'admin'], 'publish', 'posts');

// Allow an admin to delete posts.
$lock->allowRole('admin', 'delete', 'posts');

// Let's assume our caller has the role of "editor" and check some permissions.
$lock->can('read'); // true
$lock->can('delete', 'posts'); // false
$lock->can('publish'); // false: we can't publish everything, just posts.
$lock->can(['create', 'publish'], 'posts'); // true
```

Something you need to be aware of is that caller-level permissions supersede role-level permissions. Let's see how that works.

Our caller will have the user role.

```php
$lock->setRole('user');
$lock->allow('create', 'posts');
$lock->denyRole('user', 'create', 'posts');

$lock->can('create', 'posts'); // true: the user has explicit permission to create posts.
```

### Working with conditions

Conditions are actually asserts which are extra checks you can set for permissions. You can pass an array with them as the last parameter of `allow` and `deny`. All conditions must implement the `\BeatSwitch\Lock\Contracts\Condition` interface.

> **Warning:** please note that conditions currently only work with static drivers.

Let's setup a condition.

```php
<?php

use BeatSwitch\Lock\Contracts\Condition;
use BeatSwitch\Lock\Contracts\Resource;
use Illuminate\Auth\AuthManager;

class LoggedInCondition implements Condition
{
    /**
     * The Laravel AuthManager instance
     *
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    /**
     * @param \Illuminate\Auth\AuthManager $auth
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Assert if the condition is correct
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @return bool
     */
    public function assert($action, Resource $resource)
    {
        // Condition will succeed if the user is logged in.
        return $this->auth->check();
    }
}
```

Now let's see how this will work when setting up a permission.

```php
$condition = App::make('LoggedInCondition');

$lock->allow('create', 'posts', null, [$condition]);
$lock->can('create', 'posts'); // true if logged in, otherwise false.
```

You can pass along as many conditions as you like but they all need to succeed in order for the permission to work.

### Using the LockAware trait

You can easily add acl functionality to your caller by implementing the `BeatSwitch\Lock\LockAware` trait.

```php
<?php

use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\LockAware;

class Organization implements Caller
{
    use LockAware;

    public function getCallerType()
    {
        return 'organizations';
    }

    public function getCallerId()
    {
        return $this->id;
    }

    public function getCallerRoles()
    {
        return ['enterprise'];
    }
}
```

Now we need to set its lock instance.

```php
$caller->setLock($lock);
```

And now your caller can use all of the lock methods onto itself.

```php
$caller->can('create', 'posts');
$caller->allow('edit', 'pages');
```

## Api

The following methods can all be called on a `\BeatSwitch\Lock\Lock` instance.

### can

Checks to see if the current caller has permission to do something.

```
can(
    string|array $action,
    string|\BeatSwitch\Lock\Contracts\Resource $resource = null,
    int $resourceId = null
)
```

### cannot

Checks to see if it's forbidden for the current caller to do something.

```
cannot(
    string|array $action,
    string|\BeatSwitch\Lock\Contracts\Resource $resource = null,
    int $resourceId = null
)
```

### allow

Sets a `Privilege` permission on a caller to allow it to do something. Removes any matching restrictions.

```
allow(
    string|array $action,
    string|\BeatSwitch\Lock\Contracts\Resource $resource = null,
    int $resourceId = null,
    \BeatSwitch\Lock\Contracts\Condition[] $conditions = []
)
```

### deny

Sets a `Restriction` permission on a caller to prevent it from doing something. Removes any matching privileges.

```
deny(
    string|array $action,
    string|\BeatSwitch\Lock\Contracts\Resource $resource = null,
    int $resourceId = null,
    \BeatSwitch\Lock\Contracts\Condition[] $conditions = []
)
```

### toggle

Toggles the value for the given permission.

```
toggle(
    string|array $action,
    string|\BeatSwitch\Lock\Contracts\Resource $resource = null,
    int $resourceId = null
)
```

### alias

Add an alias for one or more actions.

```
alias(
    string $name,
    string|array $actions
)
```

### setRole

Set one or more roles and an optional role to inherit permissions from.

```
setRole(
    string|array $name,
    string $inherit = null
)
```

### allowRole

Sets a `Privilege` permission on one or more roles to allow them to do something. Removes any matching restrictions.

```
allowRole(
    string|array|\BeatSwitch\Lock\Contracts\Role $role,
    string|array $action,
    string|\BeatSwitch\Lock\Contracts\Resource $resource = null,
    int $resourceId = null,
    \BeatSwitch\Lock\Contracts\Condition[] $conditions = []
)
```

### denyRole

Sets a `Restriction` permission on one or more roles to prevent them from doing something. Removes any matching privileges.

```
denyRole(
    string|array|\BeatSwitch\Lock\Contracts\Role $role,
    string|array $action,
    string|\BeatSwitch\Lock\Contracts\Resource $resource = null,
    int $resourceId = null,
    \BeatSwitch\Lock\Contracts\Condition[] $conditions = []
)
```

## Building a Driver

You can easily build a driver by implementing the `BeatSwitch\Lock\Contracts\Driver` contract. Below we'll demonstrate how to create our own persistent driver using Laravel's Eloquent ORM as our storage mechanism.

We'll assume we have a `CallerPermission` model class with at least the following database columns:

- `caller_type` (varchar, 100)
- `caller_id` (int, 11)
- `type` (varchar, 10)
- `action` (varchar, 100)
- `resource_type` (varchar, 100, nullable)
- `resource_id` (int, 11, nullable)

And we have a `RolePermission` model with the following database columns:

- `role` (varchar, 100)
- `type` (varchar, 10)
- `action` (varchar, 100)
- `resource_type` (varchar, 100, nullable)
- `resource_id` (int, 11, nullable)

Let's check out a full implementation of the driver below. Notice that for the `getPermissions` method we're using the `PermissionFactory` class to easily map the data and create `Permission` objects from them.

```php
<?php

use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\Contracts\Driver;
use BeatSwitch\Lock\Contracts\Permission as PermissionContract;
use BeatSwitch\Lock\Permissions\PermissionFactory;
use CallerPermission;
use RolePermission;

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
        $permissions = CallerPermission::where('caller_type', $caller->getCallerType())
            ->where('caller_id', $caller->getCallerId())
            ->get();
        
        return PermissionFactory::createFromArray($permissions->toArray());
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
        $eloquentPermission = new CallerPermission;
        $eloquentPermission->caller_type = $caller->getCallerType();
        $eloquentPermission->caller_id = $caller->getCallerId();
        $eloquentPermission->type = $permission->getType();
        $eloquentPermission->action = $permission->getAction();
        $eloquentPermission->resource_type = $permission->getResourceType();
        $eloquentPermission->resource_id = $permission->getResourceId();
        $eloquentPermission->save();
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
        CallerPermission::where('caller_type', $caller->getCallerType())
            ->where('caller_id', $caller->getCallerId())
            ->where('type', $permission->getType())
            ->where('action', $permission->getAction())
            ->where('resource_type', $permission->getResourceType())
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
        return (bool) CallerPermission::where('caller_type', $caller->getCallerType())
            ->where('caller_id', $caller->getCallerId())
            ->where('type', $permission->getType())
            ->where('action', $permission->getAction())
            ->where('resource_type', $permission->getResourceType())
            ->where('resource_id', $permission->getResourceId())
            ->first();
    }

    /**
     * Returns all the permissions for a role
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @return \BeatSwitch\Lock\Contracts\Permission[]
     */
    public function getRolePermissions(Role $role)
    {
        $permissions = RolePermission::where('role', $role->getRoleName())->get();

        return PermissionFactory::createFromArray($permissions->toArray());
    }

    /**
     * Stores a new permission for a role
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    public function storeRolePermission(Role $role, Permission $permission)
    {
        $eloquentPermission = new RolePermission;
        $eloquentPermission->role = $role->getRoleName();
        $eloquentPermission->type = $permission->getType();
        $eloquentPermission->action = $permission->getAction();
        $eloquentPermission->resource_type = $permission->getResourceType();
        $eloquentPermission->resource_id = $permission->getResourceId();
        $eloquentPermission->save();
    }

    /**
     * Removes a permission for a role
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    public function removeRolePermission(Role $role, Permission $permission)
    {
        RolePermission::where('role', $role->getRoleName())
            ->where('type', $permission->getType())
            ->where('action', $permission->getAction())
            ->where('resource_type', $permission->getResourceType())
            ->where('resource_id', $permission->getResourceId())
            ->delete();
    }

    /**
     * Checks if a permission is stored for a role
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return bool
     */
    public function hasRolePermission(Role $role, Permission $permission)
    {
        return (bool) RolePermission::where('role', $role->getRoleName())
            ->where('type', $permission->getType())
            ->where('action', $permission->getAction())
            ->where('resource_type', $permission->getResourceType())
            ->where('resource_id', $permission->getResourceId())
            ->first();
    }
}
```

Notice that we're not checking if the permission already exists when we're attempting to store it. You don't need to worry about that because that's all been done for you in the `Lock` instance.

Now we have a driver which supports storing of permissions for callers and roles.

## Maintainer

This package is currently maintained by [Dries Vints](https://github.com/driesvints).
If you have any questions please don't hesitate to ask them in an issue.

## License

The MIT License (MIT). Please see [the License File](LICENSE.md) for more information.