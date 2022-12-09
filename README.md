# Otomaties core

## Installation

```sh
composer require tombroucke/otomaties-wp-cron-basic-auth
```

## Configuration

### Bedrock
In .env on staging website: 

```
BASIC_AUTH_USER='your-basic-auth-username'
BASIC_AUTH_PASS='your-basic-auth-your-password'
```

### Vanilla WP
In wp-config.php

```php
define('BASIC_AUTH_USER', 'your-basic-auth-username');
define('BASIC_AUTH_PASS', 'your-basic-auth-your-password');
```
