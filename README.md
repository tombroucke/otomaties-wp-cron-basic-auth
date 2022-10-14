# Otomaties core

## Installation

```sh
composer require tombroucke/otomaties-wp-cron-basic-auth
```

## Configuration

### Bedrock
In .env on staging website: 

```
BASIC_AUTH_USER='your-basic-auth-usernam'
BASIC_AUTH_PASS='your-basic-auth-your-password'
```

In config/staging.php:

```php
Config::define('BASIC_AUTH_USER', $_SERVER['BASIC_AUTH_USER'] ?? null);
Config::define('BASIC_AUTH_PASS', $_SERVER['BASIC_AUTH_PASS'] ?? null);
```

### Vanilla WP
In wp-config.php

```php
define('BASIC_AUTH_USER', 'your-basic-auth-username');
define('BASIC_AUTH_PASS', 'your-basic-auth-your-password');
```
