<?php
$db = new PDO('sqlite:c:/Users/olive/OneDrive/Documentos/Github/movimentocanonico/database/database.sqlite');
$cols = $db->query('PRAGMA table_info(produto)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo $col['name'] . "\n";
}
