<?php
    // Old entry point (root). Routing now lives in /public.
    // This file only redirects whoever still uses the old URL.
    session_start();
    header('Location: public/index.php');
    exit;