<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<?php if (0 !== count($myInstituteAccessServerList)): ?>
<h2>🏛️ Work from Home</h2>
<ul>
    <form class="home" method="get" action="getProfileList">
<?php  foreach ($myInstituteAccessServerList as $serverEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($serverEntry['base_uri']); ?>"><?=$this->l($serverEntry['display_name']); ?></button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php endif; ?>

<?php if (null !== $secureInternetServerInfo): ?>
<h2>🌍 Protect yourself Online</h2>
    <form class="home" method="get" action="getProfileList">
        <button name="baseUri" value="<?=$this->e($secureInternetServerInfo['base_uri']); ?>"><?=$this->l($secureInternetServerInfo['display_name']); ?></button>
    </form>
    <div class="add"><a class="small" href="switchLocation">Change Location...</a></div>
<?php endif; ?>

<?php if (0 !== count($myAlienServerList)): ?>
<h2>👽 Other Servers</h2>
<ul>
    <form class="home" method="get" action="getProfileList">
<?php  foreach ($myAlienServerList as $serverEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($serverEntry['base_uri']); ?>"><?=$this->l($serverEntry['display_name']); ?></button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php endif; ?>

<form class="add" method="get" action="chooseInstitute">
<button>➕</button>
</form>
<?php $this->stop('content');
