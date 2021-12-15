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