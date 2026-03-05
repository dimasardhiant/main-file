<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PayrollEntry;

$entries = PayrollEntry::latest()->take(5)->get();

foreach ($entries as $entry) {
    echo "Entry ID: " . $entry->id . "\n";
    echo "Employee: " . $entry->employee->name . "\n";
    echo "Deductions Breakdown:\n";
    print_r($entry->deductions_breakdown);
    echo "-----------------------------------\n";
}
