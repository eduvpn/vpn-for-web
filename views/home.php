<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<p><a href="../">Home</a> | <a href="../settings">Settings</a></p>
<h2>Server List</h2>
<?php if (0 !== count($myInstituteList)): ?>
<p>Choose a server to download a VPN configuration.</p>
    <ul>
        <form method="get" action="getProfileList">
<?php  foreach ($myInstituteList as $instituteEntry): ?>
        <li>
            <button name="baseUri" value="<?=$this->e($instituteEntry['base_uri']); ?>">
<?php if ('institute_access' === $instituteEntry['type']): ?>
🏛️
<?php elseif ('secure_internet' === $instituteEntry['type']): ?>
🌍
<?php else: ?>
👽
<?php endif; ?>
                <?=$this->l($instituteEntry['display_name']); ?>
            </button>
        </li>
<?php endforeach; ?>
        </form>
    </ul>
<?php endif; ?>
<p class="center">
<?php if (0 === count($myInstituteList)): ?>
    <a class="small" href="chooseServer">Add Server...</a>
<?php else: ?>
    <a class="small" href="chooseServer">Add Additional Server...</a>
<?php endif; ?>
</p>
<?php $this->stop('content');
