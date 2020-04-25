<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Add Other Server</h2>
    <form class="center" method="post" action="addOtherServer">
            <label>https:// <input type="text" name="serverName" placeholder="vpn.example.org"></label> 
            <button value="add" type="submit">Add</button>
    </form>
<?php $this->stop('content');
