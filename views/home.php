<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<h2>Home</h2>
<ul>
    <form class="home" method="get" action="getProfileList">
<?php  foreach ($myInstituteAccessServerList as $serverEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($serverEntry['base_uri']); ?>">🏛️ <?=$this->l($serverEntry['display_name']); ?></button>
    </li>
<?php endforeach; ?>
    </form>
</ul>

<?php if (null !== $secureInternetServerInfo): ?>
    <form class="home" method="get" action="getProfileList">
        <button name="baseUri" value="<?=$this->e($secureInternetServerInfo['base_uri']); ?>">🌍 <?=$this->l($secureInternetServerInfo['display_name']); ?></button>
    </form>
    <div class="add"><a class="small" href="switchLocation">🔗 Change Location...</a></div>
<?php endif; ?>

<ul>
    <form class="home" method="get" action="getProfileList">
<?php  foreach ($myAlienServerList as $serverEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($serverEntry['base_uri']); ?>">👽 <?=$this->l($serverEntry['display_name']); ?></button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<form class="add" method="get" action="chooseServer">
<button>➕</button>
</form>
<?php $this->stop('content');
