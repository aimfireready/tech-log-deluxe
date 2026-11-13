<?php

namespace Database\Seeders;

use App\Actions\Entries\SaveEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Realistic-looking entries for local development, written through SaveEntry so they are
 * sanitized and indexed exactly like real ones. Run only from DatabaseSeeder in local.
 */
class SampleEntriesSeeder extends Seeder
{
    private const SAMPLES = [
        ['Rotated the wildcard TLS certificate', '<p>Renewed <code>*.corp.example</code> and reloaded nginx on <strong>edge-1</strong> and <strong>edge-2</strong>.</p><pre><code>sudo systemctl reload nginx</code></pre>', ['Certificates', 'Web']],
        ['Opened port 8443 for the vendor portal', '<p>Firewall rule <code>ALLOW-VENDOR-8443</code> added on the edge, scoped to the vendor\'s two egress IPs.</p>', ['Firewall', 'Network']],
        ['Installed monitoring agent on db-02', '<p>Agent 7.4 installed; disk and replication-lag checks enabled.</p>', ['Monitoring', 'Database']],
        ['New starter: account and laptop for Sam P.', '<ul><li>AD account created, added to <em>Finance</em> group</li><li>Laptop LT-0412 imaged and enrolled</li></ul>', ['Staff', 'Active Directory']],
        ['Leaver: disabled account for J. Moore', '<p>Account disabled, mailbox converted to shared, MFA devices removed.</p>', ['Staff', 'Active Directory']],
        ['Changed backup retention to 35 days', '<p>Policy <strong>Nightly-Full</strong> retention raised from 14 to 35 days to cover month-end.</p>', ['Backups', 'Policy']],
        ['Patched Exchange with the cumulative update', '<p>Applied CU during the Sunday window. Restarted transport services; mail flow confirmed.</p>', ['Patching', 'Email']],
        ['Restarted the print spooler on print-01', '<p>Queue stuck again. Restarted the spooler; opened a ticket to replace the HP driver.</p>', ['Printing']],
        ['Updated DNS A record for intranet', '<p><code>intranet.corp.example</code> now points to the new load balancer.</p>', ['DNS', 'Network']],
        ['Enabled MFA for all admin accounts', '<p>Conditional access policy now requires MFA for every account in <em>Domain Admins</em>.</p>', ['Security', 'Policy', 'Active Directory']],
        ['Replaced failed disk in nas-01', '<p>Bay 3 disk replaced; RAID rebuild finished after 6h with no errors.</p>', ['Storage', 'Hardware']],
        ['Raised mailbox quota for the support team', '<p>Quota from 50 GB to 100 GB for the <em>Support</em> distribution group.</p>', ['Email', 'Policy']],
        ['Moved VLAN 30 printers to new subnet', '<p>Printers moved to <code>10.30.0.0/24</code>; DHCP reservations recreated.</p>', ['Network', 'Printing']],
        ['Upgraded PostgreSQL on db-01 to 17', '<p>pg_upgrade in link mode; downtime 4 minutes. Analyze run afterwards.</p>', ['Database', 'Patching']],
        ['Tested restore of the finance share', '<p>Restored yesterday\'s snapshot to a scratch volume; spot-checked 20 files.</p>', ['Backups', 'Storage']],
    ];

    public function run(User $author, SaveEntry $save): void
    {
        $count = count(self::SAMPLES);

        foreach (self::SAMPLES as $i => [$title, $body, $tags]) {
            // Spread over the last few weeks, always in the past whatever the viewer's timezone.
            $occurredAt = now()->subDays(($count - 1 - $i) * 2)->subHours(1 + ($i * 5) % 9)->subMinutes(($i * 17) % 60);

            $save->handle($author, [
                'title' => $title,
                'body' => $body,
                'tags' => $tags,
                'occurred_at' => $occurredAt,
            ]);
        }
    }
}
