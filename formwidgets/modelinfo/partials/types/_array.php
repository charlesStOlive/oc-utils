<?= ($field['label']) ?>
<p class="small p-l">
    <?php if(count($field['value'])) :  ?>
    <?php foreach($field['value'] as $row) :  ?>
    <?= $row ?><?php if(next( $field['value'])) { ?> <br> <?php } ?>
    <?php endforeach ?>
    <?php else :  ?>
    <i style="color: gray;">Aucun</i>
    <?php endif  ?>
</p>
