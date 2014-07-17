<?
/**
 * @var array   $links
 * @var array   $first
 * @var array   $last
 */
?>

<ul class="pagination pagination-sm">

    <? if($first): ?>
        <li>
            <a href="<?= $first['url'] ?>" title="<?= EQ::t("First page") ?>">&laquo;</a>
        </li>
    <? endif; ?>

    <? foreach($links as $link): ?>
        <? if($link['disabled']): ?>
            <li class="disabled">
        <? elseif($link['current']): ?>
            <li class="active">
        <? else: ?>
            <li>
        <? endif; ?>
                <a href="<?= $link['url'] ?>">
                    <?= $link['anchor'] ?>
                    <? if($link['current']): ?>
                        <span class="sr-only">(current)</span>
                    <? endif; ?>
                </a>
            </li>
    <? endforeach; ?>

    <? if($last): ?>
        <li>
            <a href="<?= $last['url'] ?>" title="<?= EQ::t("Last page") ?>">&raquo;</a>
        </li>
    <? endif; ?>

</ul>
 