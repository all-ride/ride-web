The routing is used to translate a requested path to the controller of the MVC library.

## A Route

A route defines a request path to an action.
A action can be any PHP callback which will handle the incoming request to a response.
Check the [Controllers](/admin/documentation/manual/page/Core/Controllers) page for more information about actions.

The definition of a route provides 2 ways for passing arguments to the action:

* A placeholder in the path with the name of the variable for a dynamic value
* A static value in the definition of the path

You can optionally set an id to a route to retrieve it in your code.
By using ids, you can easily generate URL's from your code.
It also gives you the possibility to override a path through your configuration without changing your code.

Keep your code clean by implementing only one action in an action method.
Limiting a route to a specific, or multiple request methods (GET, POST, ...) can help you with this.

## Routes.json

You can define routes through json in a file called _routes.json_.
This file goes into the config directory of the module directory structure.

An example of a routes.json file:

    {
        "routes": [
            {
                "path": "/foo",
                "controller": "vendor\\controller\\FooController"
            },
            {
                "path": "/bar/%id%/image",
                "controller": "vendor\\controller\\BarController",
                "action": "imageAction",
                "id": "bar.image",
                "methods": ["get", "head"]
            }
        ]
    }

### Minimal Route

The minimal definition of a route consists of a path and a controller.
When no action is defined, the method _indexAction_ is assumed.

    {
        "path": "/foo",
        "controller": "vendor\\controller\\FooController"
    }

### Route Per Request Method

You can define a different action for a different request method.

    {
        "path": "/blog",
        "controller": "vendor\\controller\\BlogController",
        "action": "indexAction",
        "methods": ["get", "head"]
    },
    {
        "path": "/blog",
        "controller": "vendor\\controller\\BlogController",
        "action": "saveAction",
        "methods": "post"
    }

When the path _/blog_ is requested with a GET or HEAD method, it will be translated into _BlogController->indexAction()_.
The same request with a POST method will be translated into _BlogController->saveAction()_.

### Dynamic Route

A dynamic route can be used to match everything relative to the defined path.

    {
        "path": "/web",
        "controller": "ride\\web\\mvc\\controller\\FileController",
        "id": "web",
        "dynamic": true
    }

When the path _/web/directory/file_ is requested, it will be translated into _WebController->indexAction('directory', 'file')_.

### Route With A Request Argument

Request arguments can be defined between _%_.
The name of the variable in the action method signature should be used.

    {
        "path": "/bar/%id%/image",
        "controller": "vendor\\controller\\BarController",
        "action": "imageAction",
        "id": "bar.image",
        "methods": ["get", "head"]
    }

### Route With A Predefined Argument

You can predefine arguments for the action without needing them in your request path:

    {
        "path": "/todo",
        "controller": "vendor\\controller\\WikiController",
        "action": "pageAction",
        "id": "wiki.todo",
        "arguments": [
            {
                "name": "page",
                "type": "scalar",
                "properties": {
                    "value": "Todo"
                }
            }
        ],
        "methods": ["get", "head"]
    }

This route will go to the Todo page of the wiki.

Predefined arguments are defined the same way as an argument for a dependency call.
Check the [Dependencies](/admin/documentation/manual/page/Core/Dependencies) page for a more detailed explaination.

### Route With A Dependency Controller

You can use a specific controller from your dependencies by adding the dependency id of your controller after a _#_.

    {
        "path": "/web",
        "controller": "ride\\web\\mvc\\controller\\FileController#vendor",
        "id": "web",
        "dynamic": true"
    }

Now, you only have to define a dependency for your controller interface with the id _vendor_.
Check the [Dependencies](/admin/documentation/manual/page/Core/Dependencies) page for more information about defining dependencies.

### Route For A Specific Locale

A route can specify the locale of the page.
The i18n module will set the defined locale as current locale when it's loaded.

    {
        "path": "/pagina",
        "controller": "vendor\\controller\\PageController",
        "id": "page.nl",
        "locale": "nl"
    }

### Route For A Specific Base URL

You can set a base URL for your route.

    {
        "path": "/",
        "controller": "vendor\\controller\\IndexController",
        "base": "http://www.example.com"
    }


### Include Routes From Another File

You can include a set of routes and prefix them with a path:

Assume _config/routes.json_:
```json
{
    "routes": [
        {
            "path": "/nl",
            "file": "config/custom.routes.nl.json"
        }
    ]
}
```

and _config/custom.routes.nl.json_:

```
{
    "routes": [
        {
            "path": "/",
            "controller": "vendor\\controller\\IndexController",
        },
        {
            "path": "/test",
            "controller": "vendor\\controller\\IndexController",
        }
    ]
}
```

This will result in _/nl_ and _/nl/test_.

## Obtain A URL

In PHP, you can obtain the full URL for defined routes from the Web application.
URL's are requested with a route id.
The defined path parameters of the route can be filled in when requesting a URL.

Assume the following route definition:

    {
        "routes": [
            {
                "path": "/foo",
                "controller": "vendor\\controller\\FooController"
            },
            {
                "path": "/bar/%id%/image",
                "controller": "vendor\\controller\\BarController",
                "action": "imageAction",
                "id": "bar.image",
                "methods": ["get", "head"]
            },
            {
                "path": "/web",
                "controller": "ride\\web\\mvc\\controller\\FileController",
                "id": "web",
                "dynamic": true"
            }
        ]
    }

In PHP you can generate the URLs with the following code:

    <?php

    use ride\web\WebApplication;

    function foo(WebApplication $web) {
        $urlFoo = $web->getUrl('foo');
        $urlBarImage = $web->getUrl('bar.image', array('id' => 5));

        // $urlFoo = 'http://www.example.com/foo'
        // $urlBarImage = 'http://www.example.com/bar/5/image'
    }

For dynamic routes, you just add your parameters as path variables, order does matter:

    <?php

    use ride\web\WebApplication;

    function foo(WebApplication $web) {
        $urlWeb = $web->getUrl('web');
        $urlWeb .= '/param1/param2';

        // $urlWeb = 'http://www.example.com/web/param1/param2'
    }

This web URL will be dispatched as:

    ride\web\mvc\controller\FileController->indexAction('param1', 'param2');
