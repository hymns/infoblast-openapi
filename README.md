# TM Infoblast OpenAPI Library for PHP #

Infoblast is a suite of two ways communication and messaging services available via fixed line number. This service will be offered to both existing TM fixed line business and residential customers.

Infoblast is capitalizing on mirror number to enable customer to send and receive messages via internet enable PC. Infoblast can be accessible through Infoblast Portal.

## Requirements ##
* [PHP 5.6.x or higher](http://www.php.net/)

## Installation ##

You can use **Download the Release**

### Download the Release

If you abhor using composer, you can download the package in its entirety. The [Releases](https://github.com/hymns/infoblast-openapi/releases) page lists all stable versions. Download any file
with the name `infoblast-openapi-[RELEASE_NAME].zip` for a package including this library and its dependencies.

Uncompress the zip file you download, and include the library in your project:

```php
require_once '/path/to/infoblast-openapi/openapi.php';
```

## Example Usage ##

### Get SMS from Infoblast ###
This example will get all the sms from infoblast inbox.

```php
// include openapi library
require_once 'openapi.php';

// openapi auth configuration
$config['username'] = 'infoblast_api_username';
$config['password'] = 'infoblast_api_password';

// call openapi class
$openapi = new openapi();
$openapi->initialize($config);

// get sms list - parameters 1 & 2 is optional. [default]
// parameter 1 sms status to read : [new] / old / all
// parameter 2 is for delete sms after fetch :  true / [false]
$text_messages = $openapi->get_sms('new', false);

// print sms array
print_r($text_messages);
```

### Send SMS from Infoblast ###
This example how to send sms to mobile phone or other infoblast account

```php
// include openapi library
require_once 'openapi.php';

// openapi auth configuration
$config['username'] = 'infoblast_api_username';
$config['password'] = 'infoblast_api_password';

// call openapi class
$openapi = new openapi();
$openapi->initialize($config);

// prepair data
$data['msgtype']   = 'text';
$data['to']        = '0123456789'; //separate by comma for multiple reciepient
$data['message']   = 'this message test from infoblast openapi library';

// send sms to open api
$response = $openapi->send_sms($data);

// print response
print_r($response);
```

### Get SMS Status from Infoblast ###
This example will get the sending status (above example)

```php
// include openapi library
require_once 'openapi.php';

// openapi auth configuration
$config['username'] = 'infoblast_api_username';
$config['password'] = 'infoblast_api_password';

// call openapi class
$openapi = new openapi();
$openapi->initialize($config);

// prepair data
$data['msgid']  = '12345678901234567890';

// get send status to open api
$status = $openapi->send_status($data);

// print response
print_r($status);
```
