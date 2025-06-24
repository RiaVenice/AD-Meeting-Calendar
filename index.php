<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>DATABASE CONNECTION STATUS:</h1>
    <?php
        require_once HANDLERS_PATH . '/mongodbChecker.handler.php';
        require_once HANDLERS_PATH . '/postgreChecker.handler.php';
    ?>
</body>
</html>