<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CATEGORIES = [
        'Item received/invoicing/verify with vendor.',
        'Ext/Int customers visit, training, bootcamps arrangement',
        'Onsite Power On arrangement',
        'Office space planning & allocation',
        'Assisting in hiring/recruitment/interview sessions',
        'Safety Box Tracking and Issuance',
        'PO tracking',
        'Suggest employee engagement ideas and act on it',
        'Newsletter write up and coordination',
        'Coordinate data collection when the need arise',
        'Coordinate training',
        'Manage PDL',
        'CR Booking',
        'F&B/Teambuilding event arrangement',
        'PPHW Reconciliation',
        'Gov/Stud/Customer visit',
        'Demo Accessories Keeping and Issuance',
        'Documentation',
        'Gift/Food Ordering',
        'Filling & Records Keeping',
        'CMPO Submission',
        'CW PO Track',
        'Admin Support',
        'Others',
        'Space Planning',
        'HRDF Applications',
        'New Hires/Attrition',
        'Internal Event',
        'External Event',
        'TOE Tracking and Assignment and Audits',
    ];

    private const DEPARTMENTS = ['NEXG', 'ECG', 'MCA', 'SW DEV', 'RSD'];

    public function up(): void
    {
        $now = now();

        DB::table('outcome_categories')->insertOrIgnore(
            collect(self::CATEGORIES)->map(fn ($name) => [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );

        DB::table('outcome_departments')->insertOrIgnore(
            collect(self::DEPARTMENTS)->map(fn ($name) => [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        DB::table('outcome_categories')->whereIn('name', self::CATEGORIES)->delete();
        DB::table('outcome_departments')->whereIn('name', self::DEPARTMENTS)->delete();
    }
};
