<?php
echo '<pre>';
print_r([
    "'' == 0"    => '' == 0 ? 1 : 0,
    "0 == ''"    => 0 == '' ? 1 : 0,
    "0 == 'all'" => 0 == 'all' ? 1 : 0,
    "0 == 0"     => 0 == 0 ? 1 : 0,
    "0 == '0'"   => 0 == '0' ? 1 : 0,
    "empty('0')" => empty('0') ? 1 : 0,
    "empty('1')" => empty('1') ? 1 : 0,
    "'a' == 0"   => 'a' == 0 ? 1 : 0,
    ###: "0 + ''" => 0 + '', // Fatal error:  Uncaught TypeError: Unsupported operand types: int + string
    "0 == 'a'"   => 0 == 'a' ? 1 : 0,
]);