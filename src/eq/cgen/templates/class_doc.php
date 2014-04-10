**= TO_DO : WRITE ME =**
<?= $header."\n" ?>
<?= \str_repeat('=', mb_strlen($header, 'utf-8'))."\n" ?>

<?= 'Last '.'Change: '.date("Y M j, H:s\n") ?>

[Файл](<?= $file ?>)

Описание
--------
<?= $descr_short."\n".$descr_long ?>

<? if($parent): ?>
Наследует:
    - [<?= $parent ?>](docs/<?= str_replace('\\', '/', $parent).'.md' ?>)
<? endif; ?>

<? if($implements): ?>
Реализует:
<?      foreach($implements as $interface): ?>
    - [<?= $interface ?>](docs/<?= str_replace('\\', '/', $interface).'.md' ?>)
<?      endforeach; ?>
<? endif; ?>

```phproto

namespace <?= $namespace ?> ;

class <?= $class->getShortName()."\n" ?>
{

<? if($constants): ?>
    /* Константы */
<?      foreach($constants as $constname => $constval): ?>
<? 
    if(is_string($constval))
        $constval = '"'.str_replace('"', '\"', $constval).'"';
    elseif(is_bool($constval))
        $constval = $constval ? 'true' : 'false';
    elseif(is_null($constval))
        $constval = 'null';
?>
    const <?= $constname ?> = <?= $constval ?>;
<?      endforeach; ?>

<? endif; ?>
<? if($properties): $f = 1; ?>
    /* Свойства */
<?      foreach($properties as $property):
            if($property->isPublic()) { $f = 0; }
            if($property->isProtected() && !$f) { $f = 1; echo "\n"; } ?>
    <?= $property->getDefinitionProtoString() ?> ;
<?      endforeach; ?>

<? endif; ?>
<? if($methods): $f = 1; ?>
    /* Методы */
<?      foreach($methods as $method):
            if($method->isPublic()) { $f = 0; }
            if($method->isProtected() && !$f) { $f = 1; echo "\n"; } ?>
    <?= $method->getDefinitionProtoString()."\n" ?>
<?      endforeach; ?>

<? endif; ?>
<? if($inh_methods): $f = 1; ?>
    /* Наследуемые методы */
<?      foreach($inh_methods as $method):
            if($method->isPublic()) { $f = 0; }
            if($method->isProtected() && !$f) { $f = 1; echo "\n"; } ?>
    <?= $method->getModifiersString().' '.$method->getDocReturnType().' '.
    $method->class.'::'.$method->name.' ( '.$method->getParamsProtoStr()." )\n" ?>
<?      endforeach; ?>

<? endif; ?>
}

```

<? if($constants): ?>
Константы
---------
<?      foreach($constants as $name => $value): ?>
    - *<?= $name ?>* - 
        
<?      endforeach; ?>

<? endif; ?>
<? if($properties): ?>
Свойства
--------
<?      foreach($properties as $prop): ?>
    - *<?= $prop->name ?>* - 
        <?= $prop->getDocShortDescr()."\n" ?>
<?      endforeach; ?>

<? endif; ?>
<? if($methods): ?>
Методы
------
<?      foreach($methods as $method): ?>
    - [<?= $method->name ?>](docs/<?= str_replace('\\', '/', $namespace).'/'.$class->getShortName().'/'.$method->name.'.md' ?>) - 
        <?= $method->getDocShortDescr()."\n" ?>
<?      endforeach; ?>

<? endif; ?>
<? if($inh_methods): ?>
Наследуемые методы
------------------
<?      foreach($inh_methods as $method): ?>
    - [<?= $method->class.'::'.$method->name ?>](docs/<?= str_replace('\\', '/', $method->class).'/'.$method->name.'.md' ?>) - 
        <?= $method->getDocShortDescr()."\n" ?>
<?      endforeach; ?>
<? endif; ?>

См. также
---------
<? if($see): ?>
<?      foreach($see as $link): ?>
    - <?= $link."\n" ?>
<?      endforeach; ?>
<? else: ?>
**= WRITE ME =**
<? endif; ?>
