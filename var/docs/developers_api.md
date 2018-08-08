# REST API

Read the Swagger documentation of the Kimai 2 API in your Kimai installation at `/api/doc`.

Or you can export the JSON collection by visiting `/api/doc.json`. Store the result in a file, which can be imported with Postman.

## Authentication

When calling the API you have to submit two additional header with every call for authentication:

- `X-AUTH-USER` - holds the username or email
- `X-AUTH-TOKEN` - holds the users API password, which he can set in his profile

Please make sure to ONLY call the Kimai 2 API via `https` to protect the users data!

## Calling the API with Javascript

If you develop your own extension and need to use the API for logged-in user, then you have to set the header `X-AUTH-SESSION` 
which will allow Kimai to use the current user session and not look for the default token based API authentication.

### Next step

Back to the [developer documentation](developers.md).
