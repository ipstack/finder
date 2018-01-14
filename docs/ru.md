# 1. Установка

## 1.1. Системные требования
* PHP 5.3 или выше

## 1.2. Установка с помощью composer
1. Добавьте пакет в зависимости:
    ```text
    composer require ipstack/finder
    ```
2. Подключите автозагрузку классов
    ```php
    include('vendor/autoload.php');
    ```
## Ручная установка
1. Скачайте [архив](https://github.com/ipstack/finder/archive/v1.0.0.zip)
2. Распакуйте в директорию с библиотеками проекта /path/to/libraries/ipstack/finder/
3. Подключите файлы
    ```php
    require_once('/path/to/libraries/ipstack/finder/src/Finder.php');
    ```

# 2. Использование

## 2.1. Инициализация IP Tool
```php
$finder = new \Ipstack\Finder\Finder('/path/to/ipstack.database');
```

## 2.2. Получение информации о базе данных
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
## 2.3. Поиск информации об IP адресе
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

## 2.4. Получить все элементы справочника
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

## 2.5. Получение элемента справочника по его порядковому номеру
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

# 3. Формат базы данных

|Размер|Описание|
|---|---|
|3|Контрольное слово для проверки принадлености файла к библиотеке. Всегда равно DIT|
|1|Формат unpak для чтения размера заголовка|
|1 или 4|Размер заголовка|
|1|Версия формата Ipstack|
|1|Количество справочников (RC)|
|4|Размер формата unpack описания справочников (RF)|
|RF|Формат unpack описания справочников|
|4|Размер описания одного справочника (RS)|
|RS*(RC+1)|Описания справочников|
|1024|Индекс первых октетов|
|?|БД диапазонов|
|?|БД справочника 1|
|?|БД справочника 2|
|...|...|
|?|БД справочника RC|
|4|Время создания БД в формате Unixstamp|
|128|Автор БД|
|?|Лицензия БД|
