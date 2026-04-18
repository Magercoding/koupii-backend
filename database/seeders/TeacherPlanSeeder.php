<?php

namespace Database\Seeders;

use App\Models\TeacherPlan;
use Illuminate\Database\Seeder;

class TeacherPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free Student',
                'description' => 'Perfect for students getting started with IELTS preparation.',
                'price' => 0.00,
                'benefits' => [
                    'Access to basic tests',
                    'Limited analytics dashboard',
                    'Join 1 class',
                    'Community support',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Pro Teacher',
                'description' => 'Everything a teacher needs to manage classes and track student progress.',
                'price' => 15.00,
                'benefits' => [
                    'Create unlimited classes',
                    'Full analytics & reports',
                    'Custom assignments for all skills',
                    'Priority email support',
                    'Export student data',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Institution',
                'description' => 'For schools and academies that need enterprise-grade features.',
                'price' => 99.00,
                'benefits' => [
                    'Everything in Pro Teacher',
                    'Up to 10 co-teachers per institution',
                    'Custom branding & white-label',
                    'API access & SSO integration',
                    'Dedicated account manager',
                    'SLA guarantee',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            TeacherPlan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
