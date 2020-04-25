<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Add Other Server</h2>
    <p>Here you can add your own VPN server. <strong>Only</strong> use this 
function if your VPN server is not listed under "Connect to your Institute" or "Protect yourself Online"!</p>

    <form class="center" method="post" action="addOtherServer">
            <label>https:// <input type="text" name="serverName" placeholder="vpn.example.org"></label> 
            <button value="add" type="submit">Add</button>
    </form>
<?php $this->stop('content');
