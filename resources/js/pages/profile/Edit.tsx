import React, { useState } from 'react';
import { User, KeyRound, Save } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/FormFields';
import { FlashAlert } from '../../components/ui/Toast';
import { BasePageProps } from '../../types';

interface ProfileEditProps extends BasePageProps {
  mustVerifyEmail?: boolean;
  status?: string;
}

export const ProfileEdit: React.FC<ProfileEditProps> = ({
  authUser,
  flash,
  errors,
  csrfToken,
}) => {
  const [fullname, setFullname] = useState(authUser?.fullname || '');
  const [username, setUsername] = useState(authUser?.username || '');

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <FlashAlert flash={flash} />

      {/* Header */}
      <div className="pb-4 border-b border-slate-200/80">
        <h1 className="text-xl font-bold text-slate-900 tracking-tight">
          Pengaturan Akun Pengguna
        </h1>
        <p className="text-xs text-slate-500 mt-1">
          Kelola informasi identitas profil dan pembaruan kata sandi akun Anda.
        </p>
      </div>

      {/* Section 1: Profil Information */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
              <User className="w-4 h-4" />
            </div>
            <div>
              <CardTitle>Informasi Profil</CardTitle>
              <CardDescription>Perbarui nama lengkap dan username akun Anda.</CardDescription>
            </div>
          </div>
        </CardHeader>

        <form method="POST" action="/profile">
          <CardContent className="space-y-4 max-w-lg">
            <input type="hidden" name="_token" value={csrfToken} />
            <input type="hidden" name="_method" value="PATCH" />

            <Input
              label="Nama Lengkap"
              name="fullname"
              value={fullname}
              onChange={(e) => setFullname(e.target.value)}
              required
              error={errors?.fullname?.[0]}
            />

            <Input
              label="Username / NIS / NIP"
              name="username"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
              error={errors?.username?.[0]}
            />
          </CardContent>

          <CardFooter className="justify-end">
            <Button type="submit" variant="primary" size="sm" icon={Save}>
              Simpan Profil
            </Button>
          </CardFooter>
        </form>
      </Card>

      {/* Section 2: Update Password */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 border border-slate-200/60">
              <KeyRound className="w-4 h-4" />
            </div>
            <div>
              <CardTitle>Perbarui Kata Sandi</CardTitle>
              <CardDescription>
                Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.
              </CardDescription>
            </div>
          </div>
        </CardHeader>

        <form method="POST" action="/password">
          <CardContent className="space-y-4 max-w-lg">
            <input type="hidden" name="_token" value={csrfToken} />
            <input type="hidden" name="_method" value="PUT" />

            <Input
              label="Kata Sandi Saat Ini"
              name="current_password"
              type="password"
              required
              error={errors?.current_password?.[0]}
            />

            <Input
              label="Kata Sandi Baru"
              name="password"
              type="password"
              required
              error={errors?.password?.[0]}
            />

            <Input
              label="Konfirmasi Kata Sandi Baru"
              name="password_confirmation"
              type="password"
              required
              error={errors?.password_confirmation?.[0]}
            />
          </CardContent>

          <CardFooter className="justify-end">
            <Button type="submit" variant="primary" size="sm" icon={Save}>
              Perbarui Kata Sandi
            </Button>
          </CardFooter>
        </form>
      </Card>
    </div>
  );
};
