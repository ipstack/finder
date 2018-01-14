# 1. Install

## 1.1. Requiriments
* PHP 5.3 or later

## 1.2. Install with composer
1. Add package to requires:
    ```text
    composer require ipstack/finder
    ```
2. Include autoload file
    ```php
    include('vendor/autoload.php');
    ```
## 1.3. Install without composer
1. Download [Archive](https://github.com/ipstack/finder/archive/v1.0.0.zip)
2. Unzip archive to /path/to/libraries/ipstack/finder
3. Include files
    ```php
    require_once('/path/to/libraries/ipstack/finder/src/Finder.php');
    ```

# 2. Using

## 2.1. Initialization IP Tool
```php
$finder = new \Ipstack\Finder\Finder('/path/to/ipstack.database');
```

## 2.2. Get information about created database
```php
print_r($finder->about());
```
```text
Array
(
    [created] => 1507199627
    [author] => Ivan Dudarev
    [license] => MIT
    [networks] => Array
        (
            [count] => 276148
            [data] => Array
                (
                    [country] => Array
                        (
                            [0] => code
                            [1] => name
                        )
                )
        )
)
```
## 2.3. Search information about IP Address
```php
print_r($finder->find('81.32.17.89'));
```
```text
Array
(
    [network] => Array
        (
            [0] => 81.32.0.0
            [1] => 81.48.0.0
        )
    [data] => Array
        (
            [country] => Array
                (
                    [code] => es
                    [name] => Spain
                )
        )
)
```

## 2.4. Get all items in register
```php
print_r($finder->getRegister('country'));
```
```text
Array
(
    [1] => Array
        (
            [code] => cn
            [name] => China
        )
    [2] => Array
        (
            [code] => es
            [name] => Spain
        )
...
    [N] => Array
        (
            [code] => jp
            [name] => Japan
        )
)
```

## 2.5. Get register item
```php
print_r($finder->getRegister('country',2));
```
```text
Array
    (
        [code] => cn
        [name] => China
    )
)
```

# 3. Database format

|Size|Caption|
|---|---|
|3|A control word that confirms the authenticity of the file. It is always equal to DIT|
|1|Unpack format for header size reading|
|1 or 4|Header size|
|1|Ipstack format version|
|1|Registers count (RC)|
|4|Size of registers metadata unpack formats (RF)|
|RF|Registers metadata unpack format|
|4|Size of register metadata (RS)|
|RS*(RC+1)|Registers metadata|
|1024|Index of first octets|
|?|Database of intervals|
|?|Database of Register 1|
|?|Database of Register 2|
|...|...|
|?|Database of Register RC|
|4|Unixstamp of database creation time|
|128|Author|
|?|Database license|
