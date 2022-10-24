<div id="<?= $this->getId('popup') ?>" class="wakafinder-popup">
    <?= Form::open(['data-request-parent' => "#{$parentElementId}"]) ?>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="popup">&times;</button>
            <h4 class="modal-title"><?= e(trans($title)) ?></h4>
        </div>

        <div class="wakafinder-list list-flush" data-request-data="wakafinder_flag: 1">
            <?= $searchWidget->render() ?>
            <?= $listWidget->render() ?>
        </div>

        <div class="modal-footer">
            <button
                type="button"
                class="btn btn-default"
                data-dismiss="popup">
                <?= e(trans('backend::lang.wakafinder.cancel')) ?>
            </button>
        </div>
    <?= Form::close() ?>
</div>

<script>
    setTimeout(
        function(){ $('#<?= $this->getId('popup') ?> input.form-control:first').focus() },
        310
    )
</script>
