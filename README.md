Brick\App
=========

A web application framework.

[![Build Status](https://secure.travis-ci.org/brick/app.svg?branch=master)](http://travis-ci.org/brick/app)
[![Coverage Status](https://coveralls.io/repos/brick/app/badge.svg?branch=master)](https://coveralls.io/r/brick/app?branch=master)

Installation
------------

This library is installable via [Composer](https://getcomposer.org/).
Just define the following requirement in your `composer.json` file:

    {
        "require": {
            "brick/app": "dev-master"
        }
    }

Requirements
------------

This library requires PHP 5.5 or higher. [HHVM](http://hhvm.com/) is officially supported.

Overview
--------

### Setup

We assume that you have already installed the library with Composer.

Let's create an `index.php` file that contains the simplest possible application:

    use Brick\App\Application;

    require 'vendor/autoload.php';

    $application = Application::create();
    $application->run();

If you run this file in your browser, you should get a `404` page that details an `HttpNotFoundException`. That's perfectly normal, our application is empty.

Before adding more stuff to our application, let's create a `.htaccess` file to tell Apache to redirect all requests that do not target an existing file, to our `index.php` file:

	RewriteEngine on
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule ^.*$ /index.php [L]

Now if you open any path in your browser, you should get a similar exception page. What we created here is called a [front controller](http://en.wikipedia.org/wiki/Front_Controller_pattern), and is a really handy pattern to ensure that all requests enter your application by the same door.

### Creating controllers

A controller is a piece of code that returns a `Response` for an incoming `Request`. This is where you put all the glue logic to work with models, interact with the database, and generate HTML content.

A controller can be any `callable`, but is generally a class with a number of methods that correspond to different pages or actions.

Let's create a simple controller class:

    namespace MyApp\Controller;

	use Brick\Http\Response;

	class Index
	{
	    public function hello()
	    {
	        return new Response('Hello, world');
	    }
	}

The controller class does not need to extend any particular class, and the only requirement on the controller method is that it must return a `Response` object. This requirement can even be alleviated by using a plugin that creates a Response from the controller return value.

### Adding routes

The next step is to instruct our application what controller to invoke for a given request. An object that maps a request to a controller is called a `Route`.

The application ships with a few routes that cover the most common use cases. If you have more complex requirements, you can easily write your own routes.

Let's add an off-the-box route that automatically maps the request path to the class path:

    use Brick\App\Route\StandardRoute;

    $route = new StandardRoute('MyApp\Controller');
    $application->addRoute($route);

Open your browser as `/index/hello`, you should get the "Hello, world" message.

What happened here is that our route mapped the request path `index/hello` to the class/method path `MyApp\Controller\Index::hello()`.

### Getting request data

Returning information is great, but most of the time you will need to get data from the current request first. It's very easy to get access to the `Request` object, just add it as a parameter to your method:

    public function hello(Request $request)
    {
        return new Response('Hello, ' . $request->getQuery('name'));
    }

Now if you open your browser at `/index/hello?name=John`, you should get a "Hello, John" message.

### Adding plugins

The application can already do interesting things, but is still pretty dumb. Fortunately, there is a great way to extend it with extra functionality: *plugins*.

The application ships with a few useful plugins. Let's have a look at one of them: the `RequestParamPlugin`.

This plugin allows you to automatically map request parameters to your controller parameters, with annotations. To use it, you need to install an extra package with Composer: `doctrine/annotations`.

Once this is done, let's add the plugin to our application:

    use Doctrine\Common\Annotations\AnnotationReader;
    use Brick\App\Plugin\RequestParamPlugin;

    $reader = new AnnotationReader();
    $plugin = new RequestParamPlugin($reader);
    $application->addPlugin($plugin);

That's it. Let's update our `hello()` controller once again to use this new functionality:

    namespace MyApp\Controller;

    use Brick\App\Controller\Annotation\QueryParam;
    use Brick\Http\Response;

	class Index
	{
        /**
         * @QueryParam("name")
         */
	    public function hello($name)
	    {
	        return new Response('Hello, ' . $name);
	    }
	}

*Important: the annotation needs to be imported, do not forget the `use Brick\...\QueryParam;` line.*

If you open your browser at `/index/hello?name=Bob`, you should get "Hello, Bob". We did not need to interact directly with the Request object anymore. Request variables are now automatically injected in our controller parameters. Magic.

### Writing your own plugins

You can extend the application indefinitely with the use of plugins. It's easy to write your own, as we will see through this example. Let's imagine that we want to create a plugin that begins a PDO transaction before the controller is invoked, and commits it automatically after the controller returns.

First let's have a look at the `Plugin` interface:

	interface Plugin
	{
	    public function register(EventDispatcher $dispatcher);
	}

Just one method to implement. This method allows you to register your plugin inside the application's event dispatcher, that is, tell the application which events it wants to receive. Each event is represented by a class in the `Brick\App\Event` namespace.

We can see that the two events that are called immediately before and after the controller is invoked are:

- `ControllerReadyEvent`
- `ControllerInvocatedEvent`

What we just need to do is to map each of these events to a function that does the job. Let's do it:

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

	    public function register(EventDispatcher $dispatcher)
		{
            $dispatcher->addListener(ControllerReadyEvent::class, function() {
                $this->pdo->beginTransaction();
            });

            $dispatcher->addListener(ControllerInvocatedEvent::class, function() {
                $this->pdo->commit();
            });
		}
	}

Easy as pie! Let's add our plugin to our application:

    $pdo = new PDO(/* insert parameters to connect to your database */);
    $plugin = new TransactionPlugin($pdo);
    $application->addPlugin($plugin);

We just implemented a plugin, available to all controllers in our application, in no time. This implementation is of course still naive, but does what it says on the tin, and is a good starting point for more advanced functionality.
