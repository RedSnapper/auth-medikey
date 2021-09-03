# Medikey Authentication

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rs/auth-medikey.svg?style=flat-square)](https://packagist.org/packages/rs/auth-medikey)
[![Total Downloads](https://img.shields.io/packagist/dt/rs/auth-medikey.svg?style=flat-square)](https://packagist.org/packages/rs/auth-medikey)
![GitHub Actions](https://github.com/rs/auth-medikey/actions/workflows/main.yml/badge.svg)

Medikey is the innovative service that allows the certification and the recognition of the Italian physicians: a single initial process of registration allows the physician to obtain two univocal access keys (username and password) through which one can automatically access all the reserved websites that have adhered to Medikey, avoiding new, long and repetitive registrations for each website.

## Installation

You can install the package via composer:

```bash
composer require rs/auth-medikey
```

## Usage

Before using auth-medikey, you will need to add the site id for your implementation of Medikey. These credentials should be placed in your application's config/services.php configuration file, and should use the key medikey.

```php
'medikey' => [
    'site_id' => env('MEDIKEY_SITE_ID','5')
]
```

## Authentication

To authenticate users using the Medikey provider, you will need two routes: one for redirecting the user to the provider, and another for receiving the callback from the provider after authentication. The example controller below demonstrates the implementation of both routes:

```php
use RedSnapper\Medikey\MedikeyProvider;

Route::get('/auth/redirect', function (MedikeyProvider $provider) {
    return $provider->redirect();
});

Route::get('/auth/callback', function (MedikeyProvider $provider) {
    $user = $provider->user();
});

```

The redirect method provided takes care of redirecting the user to the provider, while the user method will read the incoming request and retrieve the user's information from the provider after they are authenticated.

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email param@redsnapper.net instead of using the issue tracker.

## Credits

-   [Param Dhaliwal](https://github.com/rs)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
