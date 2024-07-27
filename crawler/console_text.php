<?php

/**
 * Outputs a string with a specific color.
 *
 * @param string $text The text to be colored.
 * @param int $color The color code.
 *
 * Available color codes:
 * 0 - default color
 * 1 - bold
 * 4 - underlined
 * 9 - strike through
 * 30 - black
 * 31 - red
 * 32 - green
 * 33 - yellow
 * 34 - blue
 * 35 - magenta
 * 36 - cyan
 * 37 - white
 */
function constyle($text="", $color=0) {
    return "\033[" . $color . "m" . $text . "\033[0m";
}



function clear_line() {
    echo "\r\033[K";
    echo "\r";
}
