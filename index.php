<?php
include BASE_PATH . "/layouts/main.layout.php";

$title = "Landing Page";

renderMainLayout(
    function () {
        ?>
        <div id="connection-status">
            <h1>Database Connection Status</h1>
            <?php
                include_once BASE_PATH . "/handlers/mongodbChecker.handler.php";
                include_once BASE_PATH . "/handlers/postgreChecker.handler.php";
            ?>
        </div>
        <?php
    },
    $title
);
?>