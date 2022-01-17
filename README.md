# CHAPI

## Why

CHAPI stands for CHIMP API Powerful Integration (clever right?).

CHAPI is our take on how to manage an easy to use but still powerful API framework.

The objectives for CHAPI are:

- Elegant and simple for the programmer
- Flexible (CHAPI comes with a lot of automagic but its optional)
- Composer Compliant

## Structure

CHAPI is aimed specifically to generate API's. This being said, the structure was created with RESTful implementations in mind. The main parts of CHAPI are the following:

### Request Object

The request object lets you play with the request information made to the API. It comes with:

- Wrapper functions to manipulate `GET`, `POST` or `PUT` request information
- Wrapper functions to use `$_SESSION` or `$_FILES`
- Wrapper functions to manipulate Cookies and Server information
- Tools to get Authorization Headers and bearers

[Request Documentation](docs/request.md)

### Response Object

The Response Object is a tool designed to facilitate the way the endpoint responds. It contains special functions to:

- Set the response status
- Set the response body
- Set any header
- Redirect to special locations
- Respond json in an easy way

[Response Documentation](docs/response.md)

### Router

CHAPI's Router is a very powerful tool. It comes with an engine that lets you add and resolve routes in a straight-forward way.

Main advantages of CHAPI's Router are:

- Easy create `GET`, `POST`, `PUT` and `DELETE` routes
- Named params
- Optional params
- Regex supported

[Router Documentation](docs/router.md)

### Endpoint Class

The endpoint class uses all the power from Request, Response and Router. It creates a base to create endpoints without too much code. Out of the box, it comes with a specific set of automagic routes that define the basis for simple endpoints needed for CrUD operations and let you extend this however you want.

[Endpoint Documentation](docs/endpoint.md)

### App Wrapper

Everything in CHAPI is wrapped, at the end, in an `APP` object that lets you access everything it has through one point.

[APP Documentation](docs/router.md)
