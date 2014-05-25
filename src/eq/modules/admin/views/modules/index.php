<?
use eq\modules\admin\ModuleHtmlHelper;
/**
 * @var eq\base\ModuleBase[] $modules
 */
?>

<h3><?= EQ::t("Modules") ?></h3>
<hr />

<? foreach($modules as $module): ?>
    <? $helper = new ModuleHtmlHelper($module); ?>
    <div class="<?= $helper->panelClass() ?>" data-module-name="<?= $module->name ?>">
        <div class="panel-heading">
            <a name="<?= $module->name ?>"><h3 class="panel-title"><?= $helper->title ?></h3></a>
            <label class="checkbox enabled-toggle">
                <?= $helper->enabledCheckbox() ?>
            </label>
        </div>
        <div class="module-name"><?= $module->name ?></div>
        <div class="panel-body">
            <p class="module-description"><?= $helper->description ?></p>
            <hr />
            <p class="module-dependencies"><?= $helper->dependencies() ?></p>
        </div>
        <? if($module->errors || $module->warnings): ?>
            <ul class="list-group">
                <? foreach($module->errors as $message): ?>
                    <li class="list-group-item list-group-item-danger"><?= $message ?></li>
                <? endforeach; ?>
                <? foreach($module->warnings as $message): ?>
                    <li class="list-group-item list-group-item-warning"><?= $message ?></li>
                <? endforeach; ?>
            </ul>
        <? endif; ?>
    </div>
<? endforeach; ?>

