<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<p><a href="../">Home</a> | <a href="../settings">Settings</a></p>
<h2>Choose Server</h2>
<p>Servers with the 🏛️ symbol connect to the institute network. Servers with 🌍 allow you to safely use the Internet from anywhere!</p>

<ul>
    <form method="post" action="addServer">
<?php  foreach ($instituteList as $instituteEntry): ?>
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
<?php $this->stop('content');
