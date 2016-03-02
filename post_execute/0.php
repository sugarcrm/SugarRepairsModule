<?php

$GLOBALS['db']->query("ALTER TABLE supp_sugarrepairs MODIFY value_before LONGTEXT");
$GLOBALS['db']->query("ALTER TABLE supp_sugarrepairs MODIFY value_after LONGTEXT");