<div data-attribute="w-collapse" data-default-lg="open">
    <div class="w-collapse__title field-section">
        <h4><?=$data['label']?></h4>
        <span class="w-collapse__icon"></span>
    </div>
    <div class="w-collapse__content">
        <?php foreach($data['children'] ?? [] as $childData) : ?>
        <?=$this->makePartial('type/'.$data['view'], ['data' => $childData])?>
        <?php endforeach ?>
    </div>
</div>