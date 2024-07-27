<?php
    include 'primary_functions.php';
    
    function main($continue) {
        if (!$continue) {
            part1();
        }
        // part2();
    }

    // Get command-line arguments
    $options = getopt("", ["continue"]);

    $continue = isset($options['continue']);
    main($continue);
?>