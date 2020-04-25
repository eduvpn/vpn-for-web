<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>

<?php if (0 === count($myInstituteAccessServerList)): ?>
    <form class="home center" method="get" action="chooseServer">
    <button>🏛️ Connect to your Institute...</button>
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
    <div class="add"><a class="small" href="chooseServer">Add Another Institute...</a></div>
<?php endif; ?>

<?php if (null === $secureInternetServerInfo): ?>
    <form class="home center" method="get" action="chooseIdP">
        <button>🌍 Protect yourself Online...</button>
    </form>
<?php else: ?>
    <form class="home center" method="get" action="getProfileList">
        <button name="baseUri" value="<?=$this->e($secureInternetServerInfo['base_uri']); ?>">🌍 <?=$this->l($secureInternetServerInfo['display_name']); ?></button>
    </form>
    <div class="add"><a class="small" href="switchLocation">Change Location...</a></div>
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
<div class="add"><a class="small" href="<?=$this->e($rootUri); ?>addOtherServer">Add Other Server...</a></div>
<?php $this->stop('content');
