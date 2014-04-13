<?php
    use \eq\base\SQLException;
    use \eq\web\html\Html;
    use \eq\dev\DevPath;
?>
<h3 class="etype"><?= $etype ?></h3>
<hr />
<table class="main-info">
    <? if($e->getCode()): ?>
        <tr>
            <td class="red">Code:</td>
            <td><?= $e->getCode() ?></td>
        </tr>
    <? endif; ?>
    <tr>
        <td class="red">Message:</td>
        <td><?= $e->getMessage() ?></td>
    </tr>
    <tr>
        <td class="red">File:</td>
        <td><?= Html::link(
            DevPath::createProjectFilePath($e->getFile()),
            DevPath::createIdeLink($e->getFile(), $e->getLine())
        ) ?> line <?= $e->getLine() ?></td>
    </tr>
</table>
<hr />
<h4>Stack Trace:</h4>
<? $step = 0; ?>
<? foreach($e->_getTrace() as $call): ?>
    <p class="trace-step">
        <span class="red">#<?= $step++ ?></span>
        <span class="code">
            <? if(isset($call['file'], $call['line'])): ?>
                <?= Html::link(
                    DevPath::createProjectFilePath($call['file']),
                    DevPath::createIdeLink($call['file'], $call['line'])
                ) ?> line <?= $call['line'] ?>
            <? else: ?>
                ...
            <? endif; ?>
        </span>
        <span class="code">
            <? if(isset($call['class'], $call['type'])): ?>
                <?= $call['class'].$call['type'].$call['function'] ?>()
            <? endif; ?>
        </span>
    </p>
<? endforeach; ?>
