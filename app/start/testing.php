<?php
/** @copyright Pley (c) 2014, All Rights Reserved */

// Adding here the Testing directories that we want Autloaded for Unit Tests
ClassLoader::addDirectories([
    app_path().'/tests/library',
]);