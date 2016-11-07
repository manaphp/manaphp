<?php

echo '<?php', PHP_EOL;

$count = 0;
while (true) {
    $hash = substr(md5(mt_rand() . microtime() . mt_rand()), 0, 16);
    if (preg_match('#[a-z]{2,}#', $hash) === 1) {
        continue;
    }
    echo '/**m0', $hash, '*/', PHP_EOL;
    if (++$count === 1000) {
        break;
    }
}
