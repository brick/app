Brick\App
=========

<img src="https://raw.githubusercontent.com/brick/brick/master/logo.png" alt="" align="left" height="64">

A web application framework.

[![Build Status](https://github.com/brick/app/workflows/CI/badge.svg)](https://github.com/brick/app/actions)
[![Coverage Status](https://coveralls.io/repos/github/brick/app/badge.svg?branch=master)](https://coveralls.io/github/brick/app?branch=master)
[![Latest Stable Version](https://poser.pugx.org/brick/app/v/stable)](https://packagist.org/packages/brick/app)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

Installation
------------

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require brick/app
```

Requirements
------------

This library requires PHP 8.0 or later.


Project status
--------------

This library is abandoned and will not receive further development.

Overview
--------

### Setup

We assume that you have already installed the library with Composer.

Let's create an `index.php` file that contains the simplest possible application:

```php
use Brick\App\Application;

require 'vendor/autoload.php';

$application = Application::create();
$application->run();
```

If you run this file in your browser, you should get a `404` page that details an `HttpNotFoundException`. That's perfectly normal, our application is empty.

Before adding more stuff to our application, let's create a `.htaccess` file to tell Apache to redirect all requests that do not target an existing file, to our `index.php` file:

```
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^.*$ /index.php [L]
```

Now if you open any path in your browser, you should get a similar exception page. What we created here is called a [front controller](http://en.wikipedia.org/wiki/Front_Controller_pattern), and is a really handy pattern to ensure that all requests enter your application by the same door.

### Creating controllers

A controller is a piece of code that returns a `Response` for an incoming `Request`. This is where you put all the glue logic to work with models, interact with the database, and generate HTML content.

A controller can be any `callable`, but is generally a class with a number of methods that correspond to different pages or actions.

Let's create a simple controller class:

```php
namespace MyApp\Controller;

use Brick\Http\Response;

class IndexController
{
    public function indexAction()
    {
        return new Response('This is the index page.');
    }

    public function helloAction()
    {
        return new Response('Hello, world');
    }
}
```

The controller class does not need to extend any particular class, and the only requirement on the controller method is that it must return a `Response` object. This requirement can even be alleviated by using a plugin that creates a Response from the controller return value.

### Adding routes

The next step is to instruct our application what controller to invoke for a given request. An object that maps a request to a controller is called a `Route`.

The application ships with a few routes that cover the most common use cases. If you have more complex requirements, you can easily write your own routes.

Let's add an off-the-box route that maps a path directory to a controller:

```php
use Brick\App\Route\SimpleRoute;

$route = new SimpleRoute([
    '/' => MyApp\Controller\IndexController::class,
]);

$application->addRoute($route);
```

Open your browser at `/`, you should get the index page message.
Open your browser at `/hello`, you should get the "Hello, world" message.

### Getting request data

Returning information is great, but most of the time you will need to get data from the current request first. It's very easy to get access to the `Request` object, just add it as a parameter to your method:

```php
public function helloAction(Request $request)
{
    return new Response('Hello, ' . $request->getQuery('name'));
}
```

Now if you open your browser at `/hello?name=John`, you should get a "Hello, John" message.

### Adding plugins

The application can already do interesting things, but is still pretty dumb. Fortunately, there is a great way to extend it with extra functionality: *plugins*.

The application ships with a few useful plugins. Let's have a look at one of them: the `RequestParamPlugin`. This plugin allows you to automatically map request parameters to your controller parameters, with attributes.

Let's add this plugin to our application:

```php
use Brick\App\Plugin\RequestParamPlugin;

$plugin = new RequestParamPlugin();
$application->addPlugin($plugin);
```

That's it. Let's update our `helloAction()` once again to use this new functionality:

```php
namespace MyApp\Controller;

use Brick\App\Controller\Attribute\QueryParam;
use Brick\Http\Response;

class Index
{
    #[QueryParam('name')]
    public function helloAction(string $name)
    {
        return new Response('Hello, ' . $name);
    }
}
```

If you open your browser at `/hello?name=Bob`, you should get "Hello, Bob". We did not need to interact directly with the Request object anymore. Request variables are now automatically injected in our controller parameters. Magic.

### Writing your own plugins

You can extend the application indefinitely with the use of plugins. It's easy to write your own, as we will see through this example. Let's imagine that we want to create a plugin that begins a PDO transaction before the controller is invoked, and commits it automatically after the controller returns.

First let's have a look at the `Plugin` interface:

```php
interface Plugin
{
    public function register(EventDispatcher $dispatcher) : void;
}
```

Just one method to implement. This method allows you to register your plugin inside the application's event dispatcher, that is, tell the application which events it wants to receive. Here is an overview of the events dispatched by the application.

#### IncomingRequestEvent

This event is dispatched as soon as the application receives a Request.

This event contains the `Request` object.

#### RouteMatchedEvent

This event is dispatched after the router has returned a match. If no match is found, the request handling is interrupted, and `ExceptionCaughtEvent` is dispatched with an `HttpNotFoundException`.

This event contains the `Request` and the `RouteMatch` objects.

#### ControllerReadyEvent

This event is dispatched when the controller is ready to be invoked. If the controller is a class method, the class will have been instantiated and this controller instance is made available to the event.

This event contains the `Request` and the `RouteMatch` objects, and the controller instance *if* the controller is a class method.

#### NonResponseResultEvent

This event is dispatched if the controller does not return a `Response` object. This event provides an opportunity for plugins to transform an arbitrary controller result into a `Response` object. For example, it could be used to JSON-encode the controller return value and wrap it into a `Response` object with the proper Content-Type header.

This event contains the `Request` and the `RouteMatch` objects, the controller instance *if* the controller is a class method, and the return value of the controller.

#### ControllerInvocatedEvent

This event is dispatched after controller invocation, regardless of whether an exception was thrown or not.

This event contains the `Request` and the `RouteMatch` objects, and the controller instance *if* the controller is a class method.

#### ResponseReceivedEvent

This event is dispatched after the controller response has been received. If an `HttpException` is caught during the controller method invocation, the exception it is converted to a `Response`, and this event is dispatched as well. Other exceptions break the application flow and don't trigger this event.

This event contains the `Request`, `Response` and `RouteMatch` objects, and the controller instance *if* the controller is a class method.

#### ExceptionCaughtEvent

This event is dispatched if an exception is caught. If the exception is not an `HttpException`, it is wrapped in an `HttpInternalServerErrorException` first, so that this event always receives an `HttpException`. A default response is created to display the details of the exception.

This event provides an opportunity to modify the default response to present a customized error message to the client.

This event contains the `HttpException`, `Request` and `Response` objects.

---

We can see that the two events that are called immediately before and after the controller is invoked are:

- `ControllerReadyEvent`
- `ControllerInvocatedEvent`

What we just need to do is to map each of these events to a function that does the job. Let's do it:

```php
use Brick\App\Event\ControllerReadyEvent;
use Brick\App\Event\ControllerInvocatedEvent;
use Brick\App\Plugin;
use Brick\Event\EventDispatcher;

class TransactionPlugin implements Plugin
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function register(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(ControllerReadyEvent::class, function() {
            $this->pdo->beginTransaction();
        });

        $dispatcher->addListener(ControllerInvocatedEvent::class, function() {
            $this->pdo->commit();
        });
    }
}
```

Easy as pie! Let's add our plugin to our application:

```php
$pdo = new PDO(/* insert parameters to connect to your database */);
$plugin = new TransactionPlugin($pdo);
$application->addPlugin($plugin);
```

We just implemented a plugin, available to all controllers in our application, in no time. This implementation is of course still naive, but does what it says on the tin, and is a good starting point for more advanced functionality.

### Sessions

The framework features a powerful alternative to native PHP sessions, allowing synchronized and non-blocking read/write to individual session entries, and supports a pluggable storage mechanism.

#### What's wrong with native PHP sessions?

Native sessions are stored in a single block of data, and **session files are locked for the entire duration of the PHP script**.
As a consequence, all requests for a single session are serialized: if several requests targeting the same session are received concurrently, they are queued and processed one after the other.
This is good enough in a traditional page-to-page browsing situation, but may cause bottlenecks when a web page issues potentially concurrent HTTP calls using AJAX.

Brick\App's session manager works differently:

- each key-value pair in the session is stored independently
- each key-value pair is only loaded when explicitly requested
- each key-value pair can be read or written without locking using `has()`, `get()`, `set()` and `remove()`
- when locking is required, a key-value pair can be read and written using the `synchronize()` method:

```php
    $session->synchronize('session-key', function($currentValue) {
        // ...
        return $newValue;
    });
```

**Only the given key is locked**, and **the lock is released as soon as the function returns.**

#### Installing the session plugin

To store your sessions in the filesystem, alongside traditional PHP sessions, just use:

```php
use Brick\App\Session\CookieSession;
use Brick\App\Plugin\SessionPlugin;

$session = new CookieSession();
$app->addPlugin(new SessionPlugin($session));
```

You can alternatively provide a custom storage adapter and use `new CookieSession($storage)` instead. A filesystem adapter and a database (PDO) adapter are provided; you can also write your own adapter by implementing `SessionStorage`.

#### Using the sessions

If you're using dependency injection in your app, you can have the `Session` object passed to your controller easily.
Just register the container in your application, and instruct it to resolve sessions:

```php
use Brick\DI\Container;
use Brick\App\Application;
use Brick\App\Session\CookieSession;
use Brick\App\Session\Session;
use Brick\App\Plugin\SessionPlugin;

// Create a DI container, and use it with our app
$container = Container::create();
$app = Application::create($container);

// Create a session, add the session plugin to our app
$session = new CookieSession();
$app->addPlugin(new SessionPlugin($session));

// Instruct the DI container to resolve the Session object 
$container->set(Session::class, $session);
```

Now the app can resolve your session automatically in your controller functions:

```php
public function indexAction(Request $request, Session $session)
{
    $userId = $session->get('user-id');
    // ...
}
```
