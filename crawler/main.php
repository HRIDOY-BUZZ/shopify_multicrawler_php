<?php
    include 'console_text.php';
    include 'extra_functions.php';
    include 'secondary_functions.php';
    include 'primary_functions.php';

    // Display MENU
    echo constyle(constyle("\n\t\t[MENU]:\n\n", 96), 4);
    echo constyle("\t1.\tRun Full Crawler\n", 93);
    echo constyle("\t2.\tRun Part-1 Only\n", 93);
    echo constyle("\t3.\tRun Part-2 Only\n", 93);
    echo constyle("\t0.\tExit\n\n", 93);

INPUT:
    // Get user input
    echo constyle("\tEnter your choice: ", 92);
    $choice = get_single_char();
    echo $choice . "\n";

    // Perform action based on user input
    switch ($choice) {
        case '1':
            clear_console();
            part1();
            part2();
            break;
        case '2':
            clear_console();
            part1();
            break;
        case '3':
            clear_console();
            part2();
            break;
        case '0':
            exit;
        default:
            echo "Invalid choice. Please try again.\n";
            goto INPUT;
    }
?>