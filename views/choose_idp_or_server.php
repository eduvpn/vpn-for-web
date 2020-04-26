<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<h2>Select your Organization or Server</h2>
<div class="add"><a href="<?=$this->e($rootUri); ?>addOtherServer">Add Unlisted Server...</a></div>
<ul>
    <form method="post" action="selectIdpOrServer">
<?php  foreach ($idpServerList as $idpServer): ?>
    <li>
<?php if (array_key_exists('org_id', $idpServer)): ?>
        <button class="org" name="orgId" value="<?=$this->e($idpServer['org_id']); ?>">🏛️ <?=$this->l($idpServer['display_name']); ?></button>
<?php else: ?>
        <button class="server" name="baseUri" value="<?=$this->e($idpServer['base_uri']); ?>">🔐 <?=$this->l($idpServer['display_name']); ?></button>
<?php endif; ?>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php $this->stop('content');
