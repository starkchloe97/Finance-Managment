<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$db = $app->make('Illuminate\Database\DatabaseManager')->connection();

// Check migrations table
$migrations = $db->select("SELECT * FROM migrations WHERE migration LIKE '%capital_draft%'");
foreach ($migrations as $m) {
    echo 'Migration: ' . $m->migration . ' | Batch: ' . $m->batch . "\n";
}

// Check all tables
$tables = $db->select("SHOW TABLES LIKE 'company_capital_%'");
foreach ($tables as $t) {
    echo 'Table: ' . implode(', ', (array)$t) . "\n";
}

// Check if companies_capital_draft_activities table exists
$exists = $db->select("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'company_capital_draft_activities'");
echo 'company_capital_draft_activities exists: ' . (count($exists) > 0 ? 'YES' : 'NO') . "\n";

// Check if companies_capital_drafts table exists  
$exists2 = $db->select("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'company_capital_drafts'");
echo 'company_capital_drafts exists: ' . (count($exists2) > 0 ? 'YES' : 'NO') . "\n";
