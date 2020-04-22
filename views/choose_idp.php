<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<p><a href="../">Home</a> | <a href="../settings">Settings</a></p>
<h2>Choose Institute</h2>
<p>Choose your Institute from the list below.</p>
<ul>
    <form method="post" action="selectIdP">
<?php  foreach ($idpList as $idpEntry): ?>
    <li>
        <button name="orgId" value="<?=$this->e($idpEntry['org_id']); ?>">
            <?=$this->l($idpEntry['display_name']); ?>
        </button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php $this->stop('content');
