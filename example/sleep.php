<?php

// sleep for $_GET['t'] seconds, fallback to 5 seconds

sleep($time = (isset($_GET['t']) ? intval($_GET['t']) : 5));

echo "I was sleeping for '$time' seconds";