# Authentication

## Features

* Token based authentication
* Admin UI to generate tokens
* Traditional login with username and password
* Traditional login with username and password for browser (Set cookie)
* OTP authentication via SMS
* Login with Social Media (Facebook, Google, Twitter, LinkedIn, GitHub)
* Register with Social Media (Facebook, Google, Twitter, LinkedIn, GitHub)
* Register with email and password

## OTP Authentication via SMS

The OTP authentication is a two-step process. First, the user is asked to enter
their phone number. If the phone number is registered to the server, the server send
randomly generated 6 digits one-time password (OTP). The OTP is valid for 5 minutes.
On successful OTP verification, the server sends an Auth Token to the user.
This Auth token can be used to authenticate the user for the next 30 days to 360 days.

### Configuration

Add the following code to your `wp-config.php` file and update the settings as described below.

```php
define( 'STACKONET_AUTH_SETTINGS', serialize( [
	'sms_provider' => 'system-log',
	'aws'          => [],
	'twilio'       => [],
] ) );
```

#### AWS SNS Settings

To user **AWS Simple Notification Service** for OTP authentication, you need to
install package `aws/aws-sdk-php` using composer.

```shell
composer require aws/aws-sdk-php

```

Add the following code to your `wp-config.php` file to enable OTP authentication
using **AWS Simple Notification Service**.

```php
define( 'STACKONET_AUTH_SETTINGS', serialize( [
    'sms_provider' => 'aws',
    'aws' =>[
        'version'   => '',
        'region'    => '',
        'key'       => '',
        'secret'    => '',
    ],
] ) );
```

#### Twilio Settings

Add the following code to your `wp-config.php` file to enable OTP authentication
using **Twilio**.

```php
define( 'STACKONET_AUTH_SETTINGS', serialize( [
    'sms_provider' => 'twilio',
    'twilio'=>[
        'from_number'   => '',
        'account_sid'   => '',
        'auth_token'    => '',
    ],
] ) );
```
