# php-prhfi-bis
A wrapper component for Prh.fi BIS v1 api
*original document* for the API => [avoindata.prh.fi](https://avoindata.prh.fi/ytj_en.html)


# Table of Contents
1. [How to Install](#install)
2. [API functions](#api-functions)
3. [Helper Function](#helper-functions)


## install 

install from composer:
```bash
$> composer require kkuzar/prhfibis
``` 
## init

construct the new class:
``` php
$bis = new kuzar\BisV1Api();
```

default options:

``` php
protected $defaultOption = [
      "hostname" => "avoindata.prh.fi",
      "port" => 443,
      "path" => "/bis/v1",
      "method" => "GET",
      "protocol" => "https"
 ];
// this class can take any config in this format
```

get a template for options

```php

 kuzar\BisV1Api()::getOptionTemplate();

 public static function getOptionTemplate()
    {
        return [
            "hostname" => "",
            "port" => 0,
            "path" => "",
            "method" => "",
            "protocol" => ""
        ];
    }

```

## API Functions

### Full Wrapper for Bis v1 API.

#### fetch by Business Id `queryCompanyDetailWithBusinessId(intput: string)`
fetch Company Full Detail with Finnish Business Id

``` php
$bis = new kuzar\BisV1Api();
$bis->queryCompanyDetailWithBusinessId($id);
```

this function will return a detail JSON as BIS website shows.

#### fetch using query param `queryCompanyDetailWithParam(Array inputObj)`

```php
$bis = new kuzar\BisV1Api();
$bis->queryCompanyDetailWithParam([
    "name" => "KES",
     // etc
]);
```
this function will return a detail JSON as BIS website shows.
This if the query is detail enough  this function will return one full detail
other wise it will return a list of potential company, more detail on BIS document in above link.

e.g.

```json
{
  "type": "fi.prh.opendata.bis",
  "version": "1",
  "totalResults": -1,
  "resultsFrom": 0,
  "previousResultsUri": null,
  "nextResultsUri": "http://avoindata.prh.fi/opendata/bis/v1?totalResults=false&maxResults=10&resultsFrom=10&name=KES&companyRegistrationFrom=2014-02-28",
  "exceptionNoticeUri": null,
  "results": [
    {
      "businessId": "3114031-3",
      "name": "Kestimestarit Oy",
      "registrationDate": "2020-01-24",
      "companyForm": "OY",
      "detailsUri": "http://avoindata.prh.fi/opendata/bis/v1/3114031-3"
    },
    {
      "businessId": "3109375-6",
      "name": "Kesar Oy",
      "registrationDate": "2020-01-07",
      "companyForm": "OY",
      "detailsUri": "http://avoindata.prh.fi/opendata/bis/v1/3109375-6"
    },
    ...
  ]
}
```
### Custom JSON return

```php
// Custom return body format
  return [
            "name" => null,
            "businessId" => null,
            "companyForm" => null,
            "website" => null,
            "latestAddr" => null,
            "latestPost" => null,
            "latestCity" => null,
            "latestBusinessCode" => null,
            "latestBusinessLine" => null,
            "latestAuxiliaryNames" => null,
        ];
```

#### fetch Company Brief Structed Information with Business ID `getCompanyStructedDataWithBusinessId(inputObj: object)`

```php

$bis = new kuzar\BisV1Api();
$bis->getCompanyStructedDataWithBusinessId("2299022-8");
```

#### fetch Company Brief Structed Information with Params `getCompanyStructedDataWithParam(inputObj: object)`

```php
$bis = new kuzar\BisV1Api();
$bis->getCompanyStructedDataWithParam([
    "name" => "KES",
     // etc
]);
```

#### example return for Structed Information:

```text
[
  {
    name: 'Suomen Ajoneuvotekniikka Oy',
    businessId: '3099016-4',
    companyForm: 'OY',
    website: '0400643313',
    latestAddr: 'Marjahaankierto 2-4',
    latestPost: 'IISALMI',
    latestCity: null,
    latestBusinessCode: '45112',
    latestBusinessLine: 'Retail sale of cars and light motor vehicles',
    latestAuxiliaryNames: 'Keski-Suomen Rengas'
  },
  {
    name: 'Kestävä Kollektiivi Oy',
    businessId: '3093045-2',
    companyForm: 'OY',
    website: 'www.kestava.net',
    latestAddr: 'Husares 1853, depto 302 1428   CABA ARGENTINA',
    latestPost: null,
    latestCity: null,
    latestBusinessCode: '71121',
    latestBusinessLine: 'Town and city planning',
    latestAuxiliaryNames: ''
  },
  ...
]
```

## Helper Functions

### getQueryBodyTemplate `getQueryTemplate()`

example code snippet
```php
kuzar\BisV1Api()::getQueryTemplate();
```

This helper will return a query object type has strutrue as following:

```php
public static function getQueryTemplate()
{
    return [
        "totalResults" => "false",
        "maxResults" => "10",
        "resultsFrom" => "0",
        "name" => "",
        "businessId" => "",
        "registeredOffice" => "",
        "streetAddressPostCode" => "",
        "companyForm" => "",
        "businessLine" => "",
        "businessLineCode" => "",
        "companyRegistrationFrom" => "",
        "companyRegistrationTo" => ""
    ];
}
```

For now it will only validate the ``companyForm`` , ``companyRegistrationFrom``, ``companyRegistrationTo`` and ``businessId``.