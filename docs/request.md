# Request

## Object Description

The request object in CHAPI was created to give the programmer an easy way to handle everything about HTTP requests.

## Methods

Here the function available:

### `isPost`

Check whether the current request was made via POST or not

#### Return values

`true` if the request was made via `POST`, `false` otherwise.

---

### `getMethod`

Return the HTTP method.

#### Return values

Returns a `string` with the HTTP method name.

---

### `readInput`

Reads the input buffer.

#### Return values

Returns a `string` with the Input Stream contents.


---

### `param`

Get a variable from the `$_REQUEST` superglobal.

#### Parameters

##### `name`

Variable name to get from `$_REQUEST`.
Default to empty `string`.

##### `default`

Default value to return if the variable is not set. Default to empty string.

#### Return values

If `name` parameter is not passed, the function will return the whole `$_REQUEST` array.
If `name` parameter is passed, the function will return variable value or default value.

---

### `get`

Get a variable from the `$_GET` superglobal.

#### Parameters

##### `name`

Variable name to get from `$_GET`.
Default to empty `string`.

##### `default`

Default value to return if the variable is not set. Default to empty string.

#### Return values

If `name` parameter is not passed, the function will return the whole `$_GET` array.
If `name` parameter is passed, the function will return variable value or default value.

---

### `post`

Get a variable from the `$_POST` superglobal.

#### Parameters

##### `name`

Variable name to get from `$_POST`.
Default to empty `string`.

##### `default`

Default value to return if the variable is not set. Default to empty string.

#### Return values

If `name` parameter is not passed, the function will return the whole `$_POST` array.
If `name` parameter is passed, the function will return variable value or default value.

---

### `session`

Get a variable from the `$_SESSION` superglobal.

#### Parameters

##### `name`

Variable name to get from `$_SESSION`.
Default to empty `string`.

##### `default`

Default value to return if the variable is not set. Default to empty string.

#### Return values

If `name` parameter is not passed, the function will return the whole `$_SESSION` array.
If `name` parameter is passed, the function will return variable value or default value.

---

### `files`

Get a variable from the `$_FILES` superglobal.

#### Parameters

##### `name`

Variable name to get from `$_FILES`.
Default to empty `string`.

#### Return values

If `name` parameter is not passed, the function will return the whole `$_FILES` array.
If `name` parameter is passed, the function will return an array with file properties or null, if name is not set.

---

### `server`

Get a variable from the `$_SERVER` superglobal.

#### Parameters

##### `name`

Variable name to get from `$_SERVER`.

##### `default`

Default value to return if the variable is not set. Default to empty string.

#### Return values

The function will return variable value for `name` or `default` value.

---

### `cookie`

Get a variable from the `$_COOKIE` superglobal.

#### Parameters

##### `name`

Variable name to get from `$_COOKIE`.

##### `default`

Default value to return if the variable is not set. Default to empty string.

#### Return values

The function will return variable value for `name` or `default` value.

---

### `hasParam`

Check the `$_REQUEST` superglobal, with or without a specific item.

#### Parameters

##### `name`

Item name.

#### Return values

`true` if the item was found (or the array is not empty), `false` otherwise.

---

### `hasGet`

Check the `$_GET` superglobal, with or without a specific item.

#### Parameters

##### `name`

Item name.

#### Return values

`true` if the item was found (or the array is not empty), `false` otherwise.

---

### `hasPost`

Check the `$_POST` superglobal, with or without a specific item.

#### Parameters

##### `name`

Item name.

#### Return values

`true` if the item was found (or the array is not empty), `false` otherwise.

---

### `hasSession`

Check the `$_SESSION` superglobal, with or without a specific item.

#### Parameters

##### `name`

Item name.

#### Return values

`true` if the item was found (or the array is not empty), `false` otherwise.

---

### `hasFiles`

Check the `$_FILES` superglobal, with or without a specific item.

#### Parameters

##### `name`

Item name.

#### Return values

`true` if the item was found (or the array is not empty), `false` otherwise.

---

### `hasServer`

Check the `$_SERVER` superglobal, with or without a specific item.

#### Parameters

##### `name`

Item name.

#### Return values

`true` if the item was found (or the array is not empty), `false` otherwise.

---

### `hasCookie`

Check the `$_COOKIE` superglobal, with or without a specific item.

#### Parameters

##### `name`

Item name.

#### Return values

`true` if the item was found (or the array is not empty), `false` otherwise.

---

### `getAuthorizationHeader`

Get authorization token from headers in different ways.

#### Return values

Bearer Token if found.

---

### `getBearerToken`

Sanitizes authorization header for use.

#### Return values

Clean bearer or null if not found.