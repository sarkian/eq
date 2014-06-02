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
        <td>
            <span class="filename"><?= htmlentities(EQ::unalias($e->getFile())) ?></span>
            line <?= $e->getLine() ?>
        </td>
    </tr>
</table>
<hr />
<h4>Stack Trace:</h4>
<? $step = 1; ?>
<? foreach($e->_getTrace() as $call): ?>
    <div class="trace-step">
        <div class="red stepnum">#<?= $step++ ?></div>
        <div class="code">
            <? if(isset($call['file'], $call['line'])): ?>
                <span class="filename"><?= htmlentities(EQ::unalias($call['file'])) ?></span>
                line <?= $call['line'] ?>
            <? else: ?>
                ...
            <? endif; ?>
        </div>
        <div class="code">
            <? if(isset($call['class'], $call['type'])): ?>
                <?= $call['class'].$call['type'].$call['function'] ?>()
            <? endif; ?>
        </div>
    </div>
<? endforeach; ?>
