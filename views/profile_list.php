<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<h2><?=$this->l($serverInfo['display_name']); ?></h2>
<?php foreach ($systemMessages as $systemMessage): ?>
<p class="plain"><?=$this->e($systemMessage['message']); ?></p>
<?php endforeach; ?>
<h3>Download Profile</h3>
<ul>
    <form method="post" action="downloadProfile">
        <input type="hidden" name="baseUri" value="<?=$this->e($baseUri); ?>">
<?php  foreach ($profileList as $profile): ?>
    <li>
        <button name="profileId" value="<?=$this->e($profile['profile_id']); ?>">
            <?=$this->l($profile['display_name']); ?>
        </button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<p><em>Import</em> the OpenVPN configuration file in your OpenVPN client after
download.</p>
<?php $this->stop('content');
