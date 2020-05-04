<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<h2>Select a Location</h2>
<p class="info">
You can select a location to use as a secure entry point to the Internet. Choose the
one closest to your physical location to have the best performance, or choose your 
home country when traveling abroad.
</p>
<ul>
    <form method="post" action="switchLocation">
<?php  foreach ($secureInternetServerList as $serverEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($serverEntry['base_url']); ?>">
            <?=$this->l($serverEntry['display_name']); ?>
        </button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php $this->stop('content');
