# Endpoint

CHAPI's endpoint infrastructure was created to give the programmer precreated functions to handle CrUD with the API while giving great flexibility to create new endpoints.

This Class is optional to use, so, a programmer can create all it's endpoint without needing to use this class.

In the other hand, it's a great tool to create quick CrUD endpoints.

## How to use it

To use the full power of the Endpoint Class you will need to implement some Models using **NORM** and **CROOD**. Read about this [here](https://).

For this example, we will be using a class called _Users_.

The basic thing to do is create an Endpoint Class that extends `CHAPI\Endpoint` like this:

```php
<?php

	class UsersEndpoint extends CHAPI\Endpoint {

		//Code here
	}
?>
```

With this basic implementation, and considering that the project has _NORM_ class `Users` and _CROOD_ class `User`, some endpoints come out of the box:

### `GET /plural`

The first automagic endpoint that will be registered will let you list a set of elements from an specific class. In this example, requesting `/users` via `GET` will let you get all the users available in database.

### `POST /plural`

This endpoint will let you create one element for a specific class. In this example, sending a `POST` request to `/users` with the data to create the user will create the user automatically and return you the created object.

### `PUT /plural/:id`

This endpoint will let you update an existing element for a specific class. In this example, sending a `PUT` request to `/users/1` with the data to update will make the user with id `1` to be updated with said data.

### `DELETE /plural/:id`

This endpoint will let you delete an existing element for a specific class. In this example, sending a `DELETE` request to `/users/1` will delete the user with id `1`.

### `GET /plural/:id`

This endpoint will let you get an existing element for a specific class. In this example, sending a `GET` request to `/users/1` will retrieve the information of user with id `1`.