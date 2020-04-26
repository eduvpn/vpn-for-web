<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>

<?php if (0 === count($myInstituteAccessServerList) && null === $secureInternetServerInfo): ?>
<form class="home center" method="get" action="chooseServerIdp">
    <button>🔐 Add your eduVPN Server</button>
</form>
<?php else: ?>
    <ul>
        <form class="home center" method="get" action="getProfileList">
<?php  foreach ($myInstituteAccessServerList as $serverEntry): ?>
        <li>
            <button name="baseUri" value="<?=$this->e($serverEntry['base_uri']); ?>">🏛️ <?=$this->l($serverEntry['display_name']); ?></button>
        </li>
<?php endforeach; ?>
        </form>
    </ul>

<?php if (null !== $secureInternetServerInfo): ?>
    <form class="home center" method="get" action="getProfileList">
        <button name="baseUri" value="<?=$this->e($secureInternetServerInfo['base_uri']); ?>">🌍 <?=$this->l($secureInternetServerInfo['display_name']); ?></button>
    </form>
    <div class="add"><a class="small" href="switchLocation">Change Location</a></div>
<?php endif; ?>

<ul>
    <form class="home center" method="get" action="getProfileList">
<?php  foreach ($myAlienServerList as $serverEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($serverEntry['base_uri']); ?>">👽 <?=$this->l($serverEntry['display_name']); ?></button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<div class="add"><a class="small" href="chooseServerIdp">Add Server</a></div>
<?php endif; ?>
<?php $this->stop('content');
