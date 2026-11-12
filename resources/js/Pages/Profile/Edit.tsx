import SumberPvcLayout from '@/Layouts/SumberPvcLayout';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    status,
}: PageProps<{ status?: string }>) {
    const user = usePage<any>().props.auth.user;

    return (
        <SumberPvcLayout>
            <Head title="Profil Akun" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-800">Pengaturan Profil</h1>
                    <p className="text-xs text-slate-500">Kelola informasi akun dan kata sandi Anda.</p>
                </div>

                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm max-w-2xl">
                    <UpdateProfileInformationForm
                        status={status}
                        className="max-w-xl"
                    />
                </div>

                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm max-w-2xl">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>

                {user.role !== 'admin' && (
                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm max-w-2xl">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                )}
            </div>
        </SumberPvcLayout>
    );
}
