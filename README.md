# PHP Hawk Authentication

This is an implementation of the [Hawk HTTP authentication scheme](https://github.com/hueniverse/hawk/).

This fork is compatible with composer.

I've fixed it to be able to echange information with firefox.

## Install

### Composer

Include this part in your composer.json:

```json
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/vinpel/PHP-Hawk"
    },
```

Then run `composer update`.

### Git

Run `git clone git://github.com/vinpel/PHP-Hawk.git /path/to/php-hawk`

## Client Usage

The client haven't change from the original version.

Assume you're hitting up the following endpoint:

`https://api.example.com/user/123?foo=bar`

And the API server has given you the following credentials:

* Key - `ghU3QVGgXM`
* Secret - `5jNP12yT17Hx5Md3DCZ5pGI5sui82efX`

To generate the header run the following:

```php
$key = 'ghU3QVGgXM';
$secret = '5jNP12yT17Hx5Md3DCZ5pGI5sui82efX';
$hawk = \Hawk\Hawk::generateHeader($key, $secret, 'GET', 'https://api.example.com/user/123?foo=bar');
```

You can also pass in additional application specific data with an `ext` key in the array.

Once you've got the Hawk string include it in your HTTP request as an `Authorization` header.

## Server Usage

On your API endpoint if the incoming request is missing an authorization header then return the following two headers:

`HTTP/1.1 401 Unauthorized`
`WWW-Authenticate: Hawk`

If the request does contain a Hawk authorization header then process it like so:

```php
$hawk = ''; // the authorisation header

// First parse the header to get the parts from the string
$hawk_parts = \Hawk\Hawk::parseHeader($hawk);

// Then with your own function, get the secret for the key from the database
$secret = getSecret($hawk_parts['id']);

// Now validate the request
$valid = \Hawk\Hawk::verifyHeader($hawk, array(
		'host'	=>	'api.example.com',
		'port'	=>	443,
		'path'	=>	'/user/123',
		'method'	=>	'GET'
	), $secret); // return true if the request is valid, otherwise false
```
