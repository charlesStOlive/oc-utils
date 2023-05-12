<p><?php if ($data['link']): ?>
 <?= $data['label'] ?> : <a style="margin-top: 5px" class="oc-<?=$data['icon']?>" href="<?=$data['link']?>" target="_blank"><?= $data['value'] ?></a>
<?php else : ?>
   <?=$data['label'] ?? 'Inconnu'?>
<?php endif ?>
</p>
