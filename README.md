Sample Offline Conversion Upload Events
============================

Sample Offline Conversion Upload Events console application can be run by Scheduler 
to upload events from an Endpoint (https://github.com/joecombopiano/fake-receipt-service) .

Prerequisites
------------

    1. Business manager
    2. Event Set Id
    3. Facebook App
    4. System User Access Token
    5. Run the Endpoint service from repos https://github.com/joecombopiano/fake-receipt-service 


INSTALLATION
------------

### Install via Composer
~~~
php composer.phar install
~~~

CONFIGURATION
-------------

### Setting parameters

Edit the file `config/env/prod/params.php` with real data, for example:

```php
return [
	'appId' => '<FACEBOOK APP ID>',
	'secretKey' => '<SECRET KEY>',
	'apiVersion' => '<API VERION>',
	'accessToken' => '<SYSTEM USER ACCESS TOKEN>',
	'eventSetId' => '<EVENT SET ID>',
	'endPoint' => '<END POINT GET DATA>' //'http://localhost:3000/receipts'
];
```

### Mapping settings
This setting use to map json key to Offline conversion keys 

Edit in the file `config/mappings.json` 

```json
{
  "mapping": {
    "match_keys.email" : "customer.email",
    "match_key.phone" : "customer.phoneNumber",
    "match_keys.ln" : "customer.lastName",
    "match_keys.fn" : "customer.firstName",
    "event_name" : "Purchase",
    "event_time" : "transactionDate",
    "currency" : "USD",
    "value" : "total"
  }
}
```

USE CONSOLE APP
-------------
Run command 

    php yii offline-conversion/upload-events


**NOTES:**
- Can set this command to run by scheduler (cron job) periodly
