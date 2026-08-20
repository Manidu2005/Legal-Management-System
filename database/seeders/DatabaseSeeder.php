<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\CourtDate;
use App\Models\Document;
use App\Models\LedgerEntry;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo data for all 5 modules.
     */
    public function run(): void
    {
        // ─── Module 4: Users ───────────────────────────────────────
        $partner = User::create([
            'name' => 'Ranil Jayasuriya',
            'email' => 'partner@lexlanka.lk',
            'password' => Hash::make('password'),
            'role' => 'partner',
            'branch' => 'Colombo Fort',
            'flat_appearance_rate' => 15000.00,
            'status' => 'active',
        ]);

        $associate = User::create([
            'name' => 'Dilani Perera',
            'email' => 'associate@lexlanka.lk',
            'password' => Hash::make('password'),
            'role' => 'associate',
            'branch' => 'Colombo Fort',
            'flat_appearance_rate' => 8000.00,
            'status' => 'active',
        ]);

        $clerk = User::create([
            'name' => 'Nuwan Fernando',
            'email' => 'clerk@lexlanka.lk',
            'password' => Hash::make('password'),
            'role' => 'clerk',
            'branch' => 'Colombo Fort',
            'flat_appearance_rate' => 0,
            'status' => 'active',
        ]);

        $associate2 = User::create([
            'name' => 'Kasun Wijesinghe',
            'email' => 'associate2@lexlanka.lk',
            'password' => Hash::make('password'),
            'role' => 'associate',
            'branch' => 'Kandy',
            'flat_appearance_rate' => 10000.00,
            'status' => 'active',
        ]);

        $suspended = User::create([
            'name' => 'Malini Dias',
            'email' => 'suspended@lexlanka.lk',
            'password' => Hash::make('password'),
            'role' => 'clerk',
            'branch' => 'Galle',
            'flat_appearance_rate' => 0,
            'status' => 'suspended',
        ]);

        // ─── Module 4: Clients ─────────────────────────────────────
        $client1 = Client::create([
            'name' => 'Sunil Bandara',
            'nic' => '199512345678',
            'phone' => '+94771234567',
            'email' => 'sunil@example.com',
            'intake_date' => '2026-01-15',
        ]);

        $client2 = Client::create([
            'name' => 'Kamala Wijeratne',
            'nic' => '198876543210',
            'phone' => '+94779876543',
            'email' => 'kamala@example.com',
            'intake_date' => '2026-02-20',
        ]);

        $client3 = Client::create([
            'name' => 'Ranjith de Silva',
            'nic' => '197654321098',
            'phone' => '+94775551234',
            'email' => null,
            'intake_date' => '2026-03-10',
        ]);

        $client4 = Client::create([
            'name' => 'Anusha Gunawardena',
            'nic' => '200198765432',
            'phone' => '+94772223344',
            'email' => 'anusha.g@example.com',
            'intake_date' => '2026-04-05',
        ]);

        // ─── Module 5: Cases ───────────────────────────────────────
        $case1 = LegalCase::create([
            'client_id' => $client1->id,
            'assigned_attorney_id' => $partner->id,
            'case_type' => 'Civil Litigation',
            'status' => 'active',
        ]);

        $case2 = LegalCase::create([
            'client_id' => $client2->id,
            'assigned_attorney_id' => $associate->id,
            'case_type' => 'Property Dispute',
            'status' => 'trial_scheduled',
        ]);

        $case3 = LegalCase::create([
            'client_id' => $client3->id,
            'assigned_attorney_id' => $partner->id,
            'case_type' => 'Criminal Defence',
            'status' => 'pending',
        ]);

        $case4 = LegalCase::create([
            'client_id' => $client1->id,
            'assigned_attorney_id' => $associate2->id,
            'case_type' => 'Family Law',
            'status' => 'active',
        ]);

        $case5 = LegalCase::create([
            'client_id' => $client4->id,
            'assigned_attorney_id' => $associate->id,
            'case_type' => 'Labour Dispute',
            'status' => 'judgment_delivered',
        ]);

        $case6 = LegalCase::create([
            'client_id' => $client2->id,
            'assigned_attorney_id' => $partner->id,
            'case_type' => 'Land Acquisition',
            'status' => 'case_closed',
        ]);

        // Demo access code (Phase 1b): Case 1 requires a code before a
        // clerk can view it or its documents. Case 2 has none configured,
        // so a clerk is denied outright — no code to enter.
        $case1->setAccessCode('247100');

        // ─── Module 2: Court Dates ─────────────────────────────────
        // Case 1 — active civil litigation
        CourtDate::create([
            'case_id' => $case1->id,
            'date' => now()->addDays(3),
            'type' => 'trial_date',
            'reminder_sent' => false,
        ]);
        CourtDate::create([
            'case_id' => $case1->id,
            'date' => now()->addDays(14),
            'type' => 'calling_date',
            'reminder_sent' => false,
        ]);
        CourtDate::create([
            'case_id' => $case1->id,
            'date' => now()->subDays(30),
            'type' => 'trial_date',
            'reminder_sent' => true,
        ]);

        // Case 2 — trial scheduled property dispute
        CourtDate::create([
            'case_id' => $case2->id,
            'date' => now()->addDays(7),
            'type' => 'trial_date',
            'reminder_sent' => false,
        ]);
        CourtDate::create([
            'case_id' => $case2->id,
            'date' => now()->addDays(21),
            'type' => 'trial_date',
            'reminder_sent' => false,
        ]);

        // Case 3 — pending
        CourtDate::create([
            'case_id' => $case3->id,
            'date' => now()->addDays(45),
            'type' => 'calling_date',
            'reminder_sent' => false,
        ]);

        // Case 4 — family law
        CourtDate::create([
            'case_id' => $case4->id,
            'date' => now()->addDays(10),
            'type' => 'trial_date',
            'reminder_sent' => false,
        ]);

        // Case 5 — past dates for judgment delivered
        CourtDate::create([
            'case_id' => $case5->id,
            'date' => now()->subDays(60),
            'type' => 'trial_date',
            'reminder_sent' => true,
        ]);
        CourtDate::create([
            'case_id' => $case5->id,
            'date' => now()->subDays(30),
            'type' => 'trial_date',
            'reminder_sent' => true,
        ]);

        // ─── Module 1: Documents ───────────────────────────────────
        Document::create([
            'case_id' => $case1->id,
            'file_path' => 'documents/case-1/contract-agreement.pdf',
            'file_type' => 'pdf',
            'category' => 'evidence',
            'uploaded_by' => $clerk->id,
        ]);

        Document::create([
            'case_id' => $case1->id,
            'file_path' => 'documents/case-1/property-photo.jpg',
            'file_type' => 'jpg',
            'category' => 'evidence',
            'uploaded_by' => $associate->id,
        ]);

        Document::create([
            'case_id' => $case2->id,
            'file_path' => 'documents/case-2/deed-of-transfer.pdf',
            'file_type' => 'pdf',
            'category' => 'deeds',
            'uploaded_by' => $partner->id,
        ]);

        Document::create([
            'case_id' => $case2->id,
            'file_path' => 'documents/case-2/survey-plan.png',
            'file_type' => 'png',
            'category' => 'evidence',
            'uploaded_by' => $clerk->id,
        ]);

        Document::create([
            'case_id' => $case3->id,
            'file_path' => 'documents/case-3/client-letter.pdf',
            'file_type' => 'pdf',
            'category' => 'correspondence',
            'uploaded_by' => $associate->id,
        ]);

        Document::create([
            'case_id' => $case4->id,
            'file_path' => 'documents/case-4/marriage-certificate.pdf',
            'file_type' => 'pdf',
            'category' => 'evidence',
            'uploaded_by' => $clerk->id,
        ]);

        // ─── Module 3: Ledger Entries ──────────────────────────────
        // Case 1 — trust + operational entries
        LedgerEntry::create([
            'case_id' => $case1->id,
            'type' => 'trust',
            'amount' => 50000.00,
            'description' => 'Client retainer deposit',
            'recorded_by' => $partner->id,
        ]);

        LedgerEntry::create([
            'case_id' => $case1->id,
            'type' => 'operational',
            'amount' => 15000.00,
            'description' => 'Court appearance fee — Trial Date 1',
            'recorded_by' => $partner->id,
        ]);

        LedgerEntry::create([
            'case_id' => $case1->id,
            'type' => 'operational',
            'amount' => 5000.00,
            'description' => 'Filing fees',
            'recorded_by' => $clerk->id,
        ]);

        // Case 2 — trust entries
        LedgerEntry::create([
            'case_id' => $case2->id,
            'type' => 'trust',
            'amount' => 75000.00,
            'description' => 'Client retainer — property dispute',
            'recorded_by' => $associate->id,
        ]);

        LedgerEntry::create([
            'case_id' => $case2->id,
            'type' => 'operational',
            'amount' => 8000.00,
            'description' => 'Court appearance fee',
            'recorded_by' => $associate->id,
        ]);

        // Case 4 — family law
        LedgerEntry::create([
            'case_id' => $case4->id,
            'type' => 'trust',
            'amount' => 30000.00,
            'description' => 'Initial retainer deposit',
            'recorded_by' => $associate2->id,
        ]);

        // Case 5 — completed case
        LedgerEntry::create([
            'case_id' => $case5->id,
            'type' => 'trust',
            'amount' => 100000.00,
            'description' => 'Full retainer payment',
            'recorded_by' => $associate->id,
        ]);

        LedgerEntry::create([
            'case_id' => $case5->id,
            'type' => 'operational',
            'amount' => 16000.00,
            'description' => '2 trial appearances × LKR 8,000',
            'recorded_by' => $associate->id,
        ]);

        LedgerEntry::create([
            'case_id' => $case5->id,
            'type' => 'operational',
            'amount' => 12000.00,
            'description' => 'Legal research and consultation',
            'recorded_by' => $associate->id,
        ]);

        $this->command->info('✓ Seeded: 5 users, 4 clients, 6 cases, 9 court dates, 6 documents, 9 ledger entries');
    }
}
