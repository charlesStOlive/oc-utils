<p class="<?=$data['class'] ?? 'sb-h2 br-s' ?>" > <?= e(trans('waka.utils::lang.workflow.state')) ?> : 
    <?php if ($data['icon']): ?><i class="<?= $data['icon'] ?>"></i><?php endif ?>
    <?=$data['value']?>
</p>
