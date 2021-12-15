# Response

## Object Description

The response object in CHAPI was created to give the programmer an easy way to handle everything about responding from its API endpoints.

By default, once an instance of `Response` is constructed, it comes with three attributes:

### `body`
Current response body, defaulted to an empty string.

### `status`
Current response status code (HTTP status), defaulted to 200.

### `headers`
Current response headers, defaulted to an empty array.

## Methods

Here the function available:

### `write`

Writes to the current response body, appends data.

#### Parameters

##### `data`
Raw response data.

---

### `setBody`

Sets the body for the current response, replaces contents (if any).

#### Parameters

##### `data`
Raw response body.

---

### `getStatus`

Gets the status code for the current response.

#### Return Values

An `integer` with the current response status code.

---

### `setStatus`

Sets the status code for the current response.

#### Parameters

##### `code`

A valid HTTP response code (200, 404, 500, etc.).

---

### `getBody`

Gets the current response body.

#### Return Values

A `string` with the response body.

---

### `setHeader`

Sets the value of an specific header for the current response.

#### Parameters

##### `name`

`string` with header name.

##### `value`

`string` with header value.

---

### `getHeader`

Gets the value of an specific header for the current response.

#### Parameters

##### `name`

`string` with header name.

#### Return Values

Header value or `null` if it's not set.

---

### `setHeaders`

Sets all the headers for the current response.

#### Parameters

##### `headers`

Headers `array`.

---

### `getHeaders`

Get the array of headers for the current response.

#### Return Values

`array` with all current headers.

---

### `redirect`

Does an HTTP redirection.

#### Parameters

##### `url`

URL to redirect to.

---

### `respond`

Flushes headers and response body.

#### Return Values

This function will always return `true`.

---

### `ajaxRespond`

Flushes headers and response body.

#### Parameters

##### `result`

`string`. Readable result for response (`success` or `error` are the most common).

##### `data`

`array` with data to respond.

##### `message`

`string`. Readable message for the response.

##### `properties`

`array`. Extra information to pass that should be outside data


#### Return Values

This function will always return `true`.

---

### `error`

Flushes headers and response body.

#### Parameters

##### `message`

`string`. Readable message for the response.

##### `status`

`integer` with HTTP code to respond, default to 404.

#### Return Values

This function will always return `true`.

---

### `badHTTPMethod`

Shortcut / Helper function to respond a "bad HTTP method" error.

#### Parameters

##### `method`

`string`. Bad method used.

#### Return Values

This function will always return `true`.