<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Error <?=$this->e($errorCode); ?></h2>

    <p>
        <?=$this->e($errorMessage); ?>
    </p>
<?php $this->stop('content');
