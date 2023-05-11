<div data-attribute="w-collapse" data-default-lg="open">
    <div class="w-collapse__title field-section">
        <h4><?=$data['label']?></h4>
        <span class="w-collapse__icon"></span>
    </div>
    <div class="w-collapse__content">
        <ul>
            <?php foreach($data['value'] as $childData) : ?>
                <li>
                    <div>-<?=$childData['label']?></br>&nbsp;&nbsp;<?=$childData['created_at']?> : <?=$childData['user']?></div>
                </li>
                
            <?php endforeach ?>
        </ul>
    </div>
</div>