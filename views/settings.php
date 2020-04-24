<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Settings</h2>
    <hr>
    <h3>Add Other Server</h3>
    <form class="center" method="post" action="addOtherServer">
            <label>https:// <input type="text" name="serverName" placeholder="vpn.example.org"></label> 
            <button value="add" type="submit">Add</button>
    </form>
    <hr>
    <form method="post" action="saveSettings">
        <fieldset>
            <label><input type="checkbox" name="forceTcp" <?=$forceTcp ? 'checked' : ''; ?>> Connect using TCP only</label> 
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
