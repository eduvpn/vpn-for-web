<!DOCTYPE html>

<html lang="en-US" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VPN for Web</title>
    <link href="css/bootstrap-reboot.min.css" media="screen" rel="stylesheet">
    <link href="css/screen.css" media="screen" rel="stylesheet">
    <link href="css/eduVPN/screen.css" media="screen" rel="stylesheet">
    <script type="text/javascript" src="js/search.js"></script>
</head>
<body>
    <header>
        <h1>VPN for Web</h1>
    </header>
    <nav>
        <a href="<?=$this->e($rootUri); ?>">Home</a> | <a href="<?=$this->e($rootUri); ?>advanced">Advanced</a>
    </nav>
    <main>
        <?=$this->section('content'); ?>
    </main>
    <footer>
    </footer>
</body>
</html>
