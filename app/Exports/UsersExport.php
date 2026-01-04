<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

    public function collection()
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Email',
            'Telepon',
            'NIP',
            'NIM',
            'NIDN',
            'Roles',
            'Status',
            'Terdaftar Pada',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone,
            $user->nip,
            $user->nim,
            $user->nidn,
            $user->roles->pluck('name')->implode(', '),
            $user->is_active ? 'Aktif' : 'Non-aktif',
            $user->created_at->format('d/m/Y H:i'),
        ];
    }
}
