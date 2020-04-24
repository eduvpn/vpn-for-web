<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Settings</h2>
    <hr>
    <h3>Add Custom Server</h3>
    <form class="center" method="post" action="addCustomServer">
            <label>https:// <input type="text" name="serverName" placeholder="vpn.example.org"></label> 
            <button value="add" type="submit">Add</button>
    </form>
    <hr>
    <form method="post" action="saveSettings">
        <fieldset>
            <label><input type="checkbox" name="forceTcp" <?=$forceTcp ? 'checked' : ''; ?>> Make VPN connect only using TCP</label> 
        </fieldset>
        <fieldset>
            <button value="apply" type="submit">Apply</button>
        </fieldset>
    </form>
    <hr>
    <form method="post" action="resetAppData">
        <fieldset>
            <button value="reset" type="submit">Logout</button>
        </fieldset>
    </form>
<?php $this->stop('content');
