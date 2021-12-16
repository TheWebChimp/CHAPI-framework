# Router


## Object Description

CHAPI's Router was created to give the programmer an easy but powerful way to add HTTP routes.

By default, once an instance of `Router` is constructed, it comes with the following:

### `base_url`
The base url particle used to construct the routes.

### `routes`
`array` that stores all the routes registered in the router.

### `default_route`
Route that will respond as default.

### `request`
Router's Request Object. Read more about this object [here](request.md).

### `response`
Router's Response Object. Read more about this object [here](response.md).

## Routes

The following are examples that will let you understand the different type of routes CHAPI lets you create.
For this examples, we will be considering that base URL is `https://chapi.test`:

### Basic route

```
/foo
```
Basic route that will run the endpoint once `https://chapi.test/foo` is reached.

### Optional particles

```
/foo[/bar]
```
Using square brackets (`[]`) you can determine if that part of the URL is optional. In this case, the endpoint with respond to  `https://chapi.test/foo` but also to  `https://chapi.test/foo/bar`

### Named Params

```
/here/{some}/{params}
```

In this case, using curly braces (`{}`) we can determine URL parameters. This parameters will be passed to the endpoint to be used inside its logic. Lets see an example of how this can be achieved:


```php
<?php
	$router->add('/here/{some}/{params}', function($some, $params) use ($router) {
		$router->getResponse()->ajaxRespond('success', func_get_args());
	});
?>
```

If you try this route the following way `https://chapi.test/here/1/2` you will get the following output:

```json
{
	"result": "success",
	"data": {
		"some": 1,
		"params": 2
	}
}
```

It's important to consider that both params should be explicitly mentioned in the endpoint function for the endpoint code to use them.

### Regex

```
/foo/\d+
/foo/(\d+)
```

Regex is extremely powerful, it's crazy. CHAPI lets you use regex as defined particles inside the route. Also, as part of the increible power of regex, if you use regex capture groups, these will be passed to the endpoint function as parameters of the function. So in the example, the first URL will respond to `https://chapi.test/foo/1337` but will not pass parameter, while the second one, if specified, will receive a first argument with the value `1337`.

### Optional Params

Using all the features already shown, you could combine optional particles and regex or named params to have *Optional params*. For example:

```
/foo[/{bar}]
```

In this case, as we know, the router will match `https://chapi.test/foo` but also `https://chapi.test/foo/bar`. The cool stuff comes next. You can register this route as the following:

```php
<?php
	$router->add('/foo[/{bar}/]', function($bar = null) use ($router) {
		$router->getResponse()->ajaxRespond('success', func_get_args());
	});
?>
```

Note that the anonymous function argument is optional? That's because that param is optional in the route!. This way, if `bar` is passed in the route, it will be passed in the `$bar` argument, and if not, it will be `null`. Nice.


### Combining everything, a practical example

Lets say you are making a blog and you would like to present the archive for an specific year, month or day. Your permalinks should look something like `posts/the_year/the_month/the_day` (very wordpress-y). But what if you want all the posts for an specific year, something like `/posts/2020` or an specific month `/2020/12`, even all the posts for all time `/posts`. If you want this flexibility, your registered route should look like this:

```
'/posts[/(\d+)[/(\d+)[/(\d+)]]]'
```

In this case, you will be telling CHAPI the following:

- If route is `/posts`, show all posts
- If route is `/posts/(\d+)`, show all posts of year.
- If route is `/posts/(\d+)/(\d+)`, show all posts of year and month.
- If route is `/posts/(\d+)/(\d+)/(\d+)`, show all posts of year, month and day.

And you could get some code like this one:

```php
<?php
	$router->add('/posts[/(\d+)[/(\d+)[/(\d+)]]]', function($year = null, $month = null, $day = null) use ($router) {

		$data = [
			'year' => $year,
			'month' => $month,
			'day' => $day
		];

		$router->getResponse()->ajaxRespond('success', $data);
	});
?>
```

In this case, using regex and optional parameters, you have a route that will respond to all the possibilities we mentioned before.

---

## Methods

Here the function available:

### `errorMessage`

Displays a generic error message in HTML.

#### Parameters

##### `message`
The error message in HTML.

##### `response_code`
The response code, default to 404.

---

### `getDefaultRoute`

Gets the default route.

#### Return Values

`string` with the default route.

---

### `getBaseUrl`

Gets base URL for router.

#### Return Values

`string` with base URL.

---

### `getRequest`

Gets router request object.

#### Return Values

`CHAPI\Request` request object.

---

### `getResponse`

Gets router response object.

#### Return Values

`CHAPI\Response` response object.

---

### `setDefaultRoute`

Sets the default route.

#### Parameters

##### `route`
Full route, defaults to '/'.

---

### `add`

Adds a new route.

#### Parameters

##### `route`
Parametrized route.

##### `func`
Handler function name.

##### `method`
Method in which to register the route. Default to *, which means *all methods*.

---

### `prepend`

Prepends a new route (puts it in first place of routes array).

#### Parameters

##### `route`
Parametrized route.

##### `func`
Handler function name.

##### `method`
Method in which to register the route.

---

### `all`

Adds or prepends a route for all methods.

#### Parameters

##### `route`
Parametrized route.

##### `func`
Handler function name.

##### `prepend`
Flag that determines if route should be prepended instead of added.

---

### `get`

Adds or prepends a route for `GET` method.

#### Parameters

##### `route`
Parametrized route.

##### `func`
Handler function name.

##### `prepend`
Flag that determines if route should be prepended instead of added.

---

### `post`

Adds or prepends a route for `POST` method.

#### Parameters

##### `route`
Parametrized route.

##### `func`
Handler function name.

##### `prepend`
Flag that determines if route should be prepended instead of added.

---

### `put`

Adds or prepends a route for `PUT` method.

#### Parameters

##### `route`
Parametrized route.

##### `func`
Handler function name.

##### `prepend`
Flag that determines if route should be prepended instead of added.

---

### `delete`

Adds or prepends a route for `DELETE` method.

#### Parameters

##### `route`
Parametrized route.

##### `func`
Handler function name.

##### `prepend`
Flag that determines if route should be prepended instead of added.

---

### `remove`

Removes the specified route.

#### Parameters

##### `route`
Parametrized route.

#### Return Values

`true` if the route was found and removed, `false` otherwise.

---

### `isRoute`

Check whether a given route exists or not.

#### Parameters

##### `route`
Parametrized route.

##### `method`
Method of the route.

#### Return Values

`true` if the route exists, `false` otherwise.

---

### `getRoutes`

Gets the registered routes.

#### Parameters

##### `route`
Parametrized route.

##### `method`
Method of the route.

#### Return Values

`array`. The registered routes.

---

### `getCurrentUrl`

Retrieves the current request URI.

#### Return Values

`string`. The current request URI.

---

### `set404`

Shortcut / Helper function that registers a route for 404

#### Parameters

##### `fn`
Function to respond to 404 route.

---

### `clearRoutes`

Remove all the registered routes

---

### `match`

Tries to match the given route with one of the registered handlers and process it.

#### Parameters

##### `spec_route`
The route to match.

#### Return Values

`true` if the route matched with a handler, `false` otherwise.

---

### `routeRequest`

Processes current request.

#### Return Values

`true` if routing has succeeded, `false` otherwise.