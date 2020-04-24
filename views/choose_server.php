<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<h2>Select your Institute</h2>
<ul>
    <form method="post" action="addServer">
<?php  foreach ($instituteList as $instituteEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($instituteEntry['base_uri']); ?>">
            <?=$this->l($instituteEntry['display_name']); ?>
        </button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php $this->stop('content');
