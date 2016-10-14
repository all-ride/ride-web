# Ride: Web

This module adds a web interface to your Ride application.

* [MVC](manual/Core/Controllers.md)
* [Router](manual/Core/Routing.md)

## What's In This Application

### Libraries

This module adds the following libraries on top of the application.

- [ride/lib-mime](https://github.com/all-ride/ride-lib-mime)
- [ride/lib-http](https://github.com/all-ride/ride-lib-http)
- [ride/lib-http-client](https://github.com/all-ride/ride-lib-http-client)
- [ride/lib-mvc](https://github.com/all-ride/ride-lib-mvc)
- [ride/lib-router](https://github.com/all-ride/ride-lib-router)

### RouterService

The _RouterService_ class is a facade to the routing subsystem.
You can use it to resolve and manage the routes.

### AbstractController

The _AbstractController_ class is the starting point for a controller in the MVC pattern.
It adds some usefull methods for retrieving system objects or setting response views.

### FileController

The _FileController_ hosts files from an internal directory.

### WebApplication

The _WebApplication_ class is the workhorse of the Ride web interface.
It implements the MVC pattern and offers events to hook in.
You can use it to resolve routes or to manipulate the sytsem flow.

## Parameters

* __http.proxy__: URL for the proxy server of the HTTP client
* __system.cache.router__: Path for the router cache file
* __system.class.request__: Class name for a new request
* __system.class.response__: Class name for a new response
* __system.default.action__: Callback for the default action when no route matched
* __system.directory.config__: Name of the config directory
* __system.http.url__: Default URL for the HTTP factory in a CLI environment where no incoming URL can be resolved
* __system.route.container.default__: Dependency id of the route container IO in use
* __system.route.container.cache__: Dependency id of the cached route container IO
* __system.session.path__: Path to the session storage
* __system.session.name__: Name of the session cookie
* __system.session.timeout__: Session timeout in seconds, defaults to 1800

## Events

* __app.exception__: Invoked when an uncatched exception is thrown. This event has the thrown exception and the web application as arguments. (exception, web)
* __app.route.pre__: Invoked before routing the request. This event has the web application as argument. (web)
* __app.route.post__: Invoked after routing the request. This event has the web application as argument. (web)
* __app.dispatch.pre__: Invoked before dispatching a request to it's controller. This event has the web application as argument (web)
* __app.dispatch.post__: Invoked adter dispatching a request to it's controller. This event has the web application as argument (web)
* __app.response.pre__: Invoked before the response is rendered and send. This event has the web application as argument (web)
* __app.response.post__: Invoked after the response has been rendered and sent. This event has the web application as argument (web)

## Related Modules 

- [ride/app](https://github.com/all-ride/ride-app)
- [ride/app-mime](https://github.com/all-ride/ride-app-mime)
- [ride/cli-web](https://github.com/all-ride/ride-cli-web)
- [ride/setup-web](https://github.com/all-ride/ride-setup-web)
- [ride/web](https://github.com/all-ride/ride-web)
- [ride/web-i18n](https://github.com/all-ride/ride-web-i18n)
- [ride/web-image](https://github.com/all-ride/ride-web-image)
- [ride/web-minifier](https://github.com/all-ride/ride-web-minifier)
- [ride/web-security](https://github.com/all-ride/ride-web-security)
- [ride/web-template](https://github.com/all-ride/ride-web-template)
- [ride/wra](https://github.com/all-ride/ride-wra)

## Installation

You can use [Composer](http://getcomposer.org) to install this application.

```
composer require ride/setup-web
```

or for manual install:

```
composer require ride/web
```
