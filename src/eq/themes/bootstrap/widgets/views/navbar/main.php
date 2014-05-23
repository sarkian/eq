<?php
/**
 * @var eq\themes\bootstrap\widgets\Navbar $bar
 */
?>
<div class="<?= $bar->nav_class ?>" role="navigation">
    <div class="container-fluid">

        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse"
                    data-target="#<?= $bar->collapse_id ?>">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <? if($bar->brand_title): ?>
                <a class="navbar-brand" href="<?= $bar->brand_link ?>"><?= $bar->brand_title ?></a>
            <? endif; ?>
        </div>

        <div class="collapse navbar-collapse" id="<?= $bar->collapse_id ?>">
            <ul class="nav navbar-nav">
                <? foreach($bar->items as $name => $item): ?>
                    <? if($bar->isItemVisible($item)): ?>
                        <?= $bar->renderItem($item); ?>
                    <? endif; ?>
                <? endforeach; ?>
            </ul>
        </div>

    </div>
</div>