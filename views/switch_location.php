<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<h2>Select a Location</h2>
<ul>
    <form method="post" action="switchLocation">
<?php  foreach ($secureInternetServerList as $serverEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($serverEntry['base_uri']); ?>">
            <?=$this->l($serverEntry['display_name']); ?>
        </button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php $this->stop('content');
