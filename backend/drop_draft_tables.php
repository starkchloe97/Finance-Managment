<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$db = $app->make('Illuminate\Database\DatabaseManager')->connection();

// Force drop the activities table
$db->statement('SET FOREIGN_KEY_CHECKS = 0');
$db->statement('DROP TABLE IF EXISTS company_capital_draft_activities');
$db->statement('DROP TABLE IF EXISTS company_capital_drafts');
$db->statement('SET FOREIGN_KEY_CHECKS = 1');

// Remove migration record for drafts table (since we dropped it)
$db->table('migrations')->where('migration', 'LIKE', '%company_capital_drafts%')->delete();

// Verify
$tables = $db->select("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'company_capital_%'");
echo "Remaining company_capital tables:\n";
foreach ($tables as $t) {
    echo '  ' . (array)$t['table_name'] . "\n";
}
echo "Done.\n";
