#CA certificates

you can download ca certificates from [here](https://curl.haxx.se/ca/cacert.pem)

```php
<?php
['httpClient'=>['caFile'=>'@config/ca.pem']];
```