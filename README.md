MemcachedPHP
============

PHP memcached library

```php
$mc = new MemcachedPHP('127.0.0.1', 11211);

$data = array('test' => 'someval');

$mc->set('some_var', $data, 300); // true

$data = $mc->get('some_var'); // array('test' => 'someval')

$mc->delete('some_var'); // true

```
