---
    title: Http客户端
---

# HTTP Client
## Overview
`ManaPHP\Curl\Easy` provides an easy interface for performing Hyper-Text Transfer Protocol (HTTP) requests.
`ManaPHP\Curl\Easy` supports the most simple features expected from an HTTP client,as well as some more complex features such as HTTP authentication and file uploads. 

## Quick Start
The class constructor optionally accepts options as its first parameter, and headers as its second parameter.

```php
    <?php
    $options=['timeout' => 2];
    $headers=['User-Agent' => 'manaphp/httpClient'];
    $httpClient=new \ManaPHP\Http\Client\Adapter\Curl($options, $headers);
```

## Configuration
`ManaPHP\Curl\Easy` supports the options as follows:

|Parameter        |Description                                        |Expected Values|Default Value|
|:----------------|:--------------------------------------------------|:--------------|:------------|
|timeout          |Connection timeout (seconds)                       |integer        |10           |
|max_redirects    | Maximum number of redirects to follow (0 = none)  |integer        |10           |
|file             |File to stream the body to instead.                |string         |''           |
|proxy            |Proxy details to use for proxy                     |string         |''           |
|ssl_certificates |Should we verify SSL certificates? Allows passing in a custom certificate file as a string.|string |xx/ca.pem|
|verify_host      |Should we verify the common name in the SSL certificate?|boolean|true|

## GET
The GET method means retrieve whatever information (in the form of an entity) is identified by the Request-URI. 
Adding `get` parameters to an HTTP request is quite simple, and can be done either by specifying them as part of the URL or by using array format method.
```php
    <?php
    $httpClient = new \ManaPHP\ManaPHP\Curl\Easy();
    
    // as parts of the URl
    $statusCode = $httpClient->get('http://apis.juhe.cn/ip/ip2addr?ip=127.0.0.1');
    $responseBody=$httpClient->getResponseBody();
    
    // as array format
    $statusCode = $httpClient->get(['http://apis.juhe.cn/ip/ip2addr',['ip'=>'127.0.0.1']);
    $responseBody=$httpClient->getResponseBody();
```
## POST
The POST method is used to request that the origin server accept the entity enclosed in the request as a new subordinate of the resource identified by the Request-URI in the Request-Line.
Adding `post` parameters to a request is very similar to adding `get` parameters.
```php
    $statusCode = $httpClient->post('http://apis.juhe.cn/ip/ip2addr',['ip'=>'www.baidu.com']);
    $responseBody=$httpClient->getResponseBody();
```
## PUT
The PUT method requests that the enclosed entity be stored under the supplied Request-URI.  
```php
    $statusCode = $httpClient->put('http://apis.juhe.cn/ip/ip2addr',['ip'=>'www.baidu.com']);
    $responseBody=$httpClient->getResponseBody();
```    
## PATCH
```php
    $statusCode = $httpClient->patch('http://apis.juhe.cn/ip/ip2addr',['ip'=>'www.baidu.com']);
    $responseBody=$httpClient->getResponseBody();
```
## DELETE
The DELETE method requests that the origin server delete the resource identified by the Request-URI. 
```php
    $statusCode = $httpClient->delete('http://apis.juhe.cn/ip/ip2addr');
    $responseBody=$httpClient->getResponseBody();
```
