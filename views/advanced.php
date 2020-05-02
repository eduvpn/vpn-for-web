<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Advanced</h2>
    <h3>Add Other Server</h3>
    <p>Here you can add your own VPN server not (yet) officially part of eduVPN.</p>

    <form class="center" method="post" action="addOtherServer">
            <input type="text" name="serverName" placeholder="vpn.example.org" autofocus>
<fieldset><button value="add" type="submit">Add</button></fieldseT>
    </form>


    <h3>Settings</h3>

    <form method="post" action="saveSettings">
        <fieldset>
            <label><input type="checkbox" name="forceTcp" <?=$forceTcp ? 'checked' : ''; ?>> Connect using TCP only</label> 
        </fieldset>

        <fieldset>
            <button value="apply" type="submit">Apply</button>
        </fieldset>
    </form>

    <h3>Logout</h3>

    <form method="post" action="resetAppData">
        <fieldset>
            <button value="reset" type="submit">Logout</button>
        </fieldset>
    </form>
<?php $this->stop('content');
