<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<h2>Download Profile</h2>
<h3><?=$this->l($serverInfo['display_name']); ?></h3>
<ul>
    <form method="post" action="downloadProfile">
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
