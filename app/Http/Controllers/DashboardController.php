<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Show dashboard page.
     */
    public function index()
    {
        $user = auth()->user();

        // Get statistics based on role
        $stats = [
            'jadwal' => 24,
            'mataKuliah' => 12,
            'dosen' => 18,
            'mahasiswa' => 156,
        ];

        // Get today's schedule (sample data - will be replaced with real data)
        $todaySchedules = [
            [
                'time' => '08:00 - 10:30',
                'subject' => 'Metodologi Penelitian',
                'room' => 'Ruang 301',
                'lecturer' => 'Dr. Ahmad Fauzi, M.Pd',
                'status' => 'ongoing',
            ],
            [
                'time' => '13:00 - 15:30',
                'subject' => 'Manajemen Strategik',
                'room' => 'Ruang 302',
                'lecturer' => 'Prof. Dr. Siti Aminah',
                'status' => 'upcoming',
            ],
            [
                'time' => '16:00 - 18:30',
                'subject' => 'Statistik Lanjutan',
                'room' => 'Lab Komputer',
                'lecturer' => 'Dr. Budi Santoso',
                'status' => 'upcoming',
            ],
        ];

        // Recent activities
        $recentActivities = [
            [
                'type' => 'jadwal',
                'message' => 'Jadwal Metodologi Penelitian diperbarui',
                'time' => '5 menit yang lalu',
            ],
            [
                'type' => 'nilai',
                'message' => 'Nilai UTS Manajemen Strategik telah diinput',
                'time' => '1 jam yang lalu',
            ],
            [
                'type' => 'absensi',
                'message' => 'Absensi pertemuan ke-8 telah dicatat',
                'time' => '2 jam yang lalu',
            ],
        ];

        // Notifications
        $notifications = [
            [
                'title' => 'Perubahan Jadwal',
                'message' => 'Jadwal Statistik Lanjutan dipindahkan ke Ruang 305',
                'time' => '10 menit yang lalu',
                'read' => false,
            ],
            [
                'title' => 'Pengingat SK Mengajar',
                'message' => 'SK Mengajar semester ini belum digenerate',
                'time' => '1 jam yang lalu',
                'read' => false,
            ],
            [
                'title' => 'Deadline Input Nilai',
                'message' => 'Batas waktu input nilai UTS: 3 hari lagi',
                'time' => '3 jam yang lalu',
                'read' => true,
            ],
        ];

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'todaySchedules' => $todaySchedules,
            'recentActivities' => $recentActivities,
            'notifications' => $notifications,
        ]);
    }
}
