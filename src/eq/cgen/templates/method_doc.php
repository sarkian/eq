**= TO_DO : WRITE ME =**
<?= $header."\n" ?>
<?= \str_repeat('=', mb_strlen($header, 'utf-8'))."\n" ?>

<?= 'Last '.'Change: '.date("Y M j, H:s\n") ?>

[Класс](docs/<?= str_replace('\\', '/', $class->name).'.md' ?>)
[Файл](<?= $file ?>)

Описание
--------
<?= $descr_short."\n".$descr_long ?>

```phproto
    
    <?= $method->getModifiersString().' '.$method->getDocReturnType().' '.$class->name.'::'.$method->name.' ( '.$method->getParamsProtoStr()." )\n" ?>

```

<? if($params): ?>
Параметры
---------
<?      foreach($params as $param): ?>
    - *<?= $param->name ?>* - 
        <?= $param->getDocDescription()."\n" ?>
<?      endforeach; ?>
<? endif; ?>

<? if($method->getDocReturnType() !== 'void'): ?>
Возвращаемые значения
---------------------
    <?= $method->getDocReturnDescr()."\n" ?>
<? endif; ?>
<? if($throws): ?>
Возможные исключения
--------------------
<?      foreach($throws as $description): ?>
    - <?= $description."\n" ?>
<?      endforeach; ?>
<? endif; ?>

Примеры
-------
```php
<?= "<?\n" ?>

    /* WRITE ME */

```

См. также
---------
<? if($see): ?>
<?      foreach($see as $link): ?>
    - <?= $link."\n" ?>
<?      endforeach; ?>
<? else: ?>
**= WRITE ME =**
<? endif; ?>
