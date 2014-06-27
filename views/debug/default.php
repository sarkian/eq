<?php

// TODO: Arguments dump

/**
 * @var ExceptionBase $e
 */

use eq\base\ExceptionBase;
use eq\cgen\Highlighter;
use eq\helpers\Str;

$ns = Str::classNamespace($e);
if($ns)
    $ns .= "\\";

$trace = method_exists($e, "_getTrace") ? $e->_getTrace() : $e->getTrace();

?>


<h3>
    <span class="mute"><?= $ns ?></span><span><?= Str::classBasename($e) ?></span>
</h3>
<hr />

<table class="exception-info-table">
    <?php if($e->getCode()): ?>
        <tr>
            <td class="row-name">Code:</td>
            <td class="row-value"><?= $e->getCode() ?></td>
        </tr>
    <?php endif; ?>
    <tr>
        <td class="row-name">Message:</td>
        <td class="row-value"><?= $e->getMessage() ?></td>
    </tr>
<!--    <tr>-->
<!--        <td class="row-name">File:</td>-->
<!--        <td class="row-value">-->
<!--            <span class="filename">--><?//= htmlentities(EQ::unalias($e->getFile())) ?><!--</span>-->
<!--            line <span class="filename">--><?//= $e->getLine() ?><!--</span>-->
<!--        </td>-->
<!--    </tr>-->
</table>
<hr />

<!--<h4>Stack Trace</h4>-->

<div class="trace-step">
    <div class="trace-step-header no-border">
        <span class="trace-step-location">
            <span class="filename"><?= htmlentities(EQ::unalias($e->getFile())) ?></span>
            line <span class="filename"><?= $e->getLine() ?></span>
        </span>
        <a href="#" class="toggle-code-link" title="Show/hide code"
                onclick="toggleCode('first'); return false;"><></a>
    </div>
    <div class="trace-step-code" id="code-first">
        <?= Highlighter::file($e->getFile())
            ->render($e->getLine() - 6, $e->getLine() + 6, $e->getLine()) ?>
<!--        <div class="gradient-top"></div>-->
<!--        <div class="gradient-bottom"></div>-->
    </div>
</div>

<? foreach($trace as $i => $call): ?>
    <div class="trace-step">
        <div class="trace-step-header">
            <span class="trace-step-num">#<?= $i ?></span>
            <span class="trace-step-location">
                <? if(isset($call['file'], $call['line'])): ?>
                    <span class="filename"><?= htmlentities(EQ::unalias($call['file'])) ?></span>
                    line <span class="filename"><?= $call['line'] ?></span>
                <? else: ?>
                        ...
                <? endif; ?>
            </span>
            <? if(isset($call['file'], $call['line'])): ?>
                <a href="#" class="toggle-code-link" title="Show/hide code"
                        onclick="toggleCode('<?= $i ?>'); return false;"><></a>
            <? endif; ?>
        </div>
        <div class="trace-step-desc">
            <? if(isset($call['class'], $call['type'])): ?>
                <span><?= $call['class'] ?></span>
                <span class="hl-operator"><?= $call['type'] ?></span>
            <? endif; ?>
            <span class="funcname"><?= $call['function'] ?></span>
            <span class="hl-operator">(</span>
            <span class="args">
                <? if($call['args']): ?>
                    <a href="#" class="toggle-args-link" title="Show/hide arguments">
                        &nbsp;&darr;&nbsp;
                    </a>
                <? endif; ?>
            </span>
            <span class="hl-operator">)</span>
        </div>
        <? if(isset($call['file'], $call['line'])): ?>
            <div class="trace-step-code<?= $i > 2 ? ' collapsed' : '' ?>" id="code-<?= $i ?>">
                <?= Highlighter::file($call['file'])
                    ->render($call['line'] - 6, $call['line'] + 6, $call['line']) ?>
<!--                <div class="gradient-top"></div>-->
<!--                <div class="gradient-bottom"></div>-->
            </div>
        <? endif; ?>
    </div>
<? endforeach; ?>

