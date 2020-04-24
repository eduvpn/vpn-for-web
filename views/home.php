<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>

<?php if (0 === count($myInstituteServerInfo)): ?>
    <form class="home center" method="get" action="chooseServer">
    <button>🏛️ Connect to your Institute</button>
    </form>
<?php else: ?>
    <h2>Connect to your Institute</h2>
    <ul>
        <form method="get" action="getProfileList">
<?php  foreach ($myInstituteServerInfo as $instituteEntry): ?>
        <li>
            <button name="baseUri" value="<?=$this->e($instituteEntry['base_uri']); ?>">🏛️ <?=$this->l($instituteEntry['display_name']); ?></button>
        </li>
<?php endforeach; ?>
        </form>
    </ul>
    <div class="add"><a class="small" href="chooseServer">Add Another Institute...</a></div>
<?php endif; ?>

<?php if (null === $secureInternetServerInfo): ?>
    <form class="home center" method="get" action="chooseIdP">
    <button>🌍 Protect Yourself Online</button>
    </form>
<?php else: ?>
    <h2>Protect Yourself Online</h2>
    <form class="home center" method="get" action="getProfileList">
    <button name="baseUri" value="<?=$this->e($secureInternetServerInfo['base_uri']); ?>">🌍 <?=$this->l($secureInternetServerInfo['display_name']); ?></button>
    </form>
    <div class="add"><a class="small" href="switchLocation">Switch Location...</a></div>
<?php endif; ?>

<?php $this->stop('content');
