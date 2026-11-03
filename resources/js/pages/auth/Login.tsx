import React, { useState, useEffect } from 'react';
import { motion } from 'motion/react';
import { GraduationCap, AlertCircle, ArrowRight, CheckCircle2, Info, KeyRound, ShieldCheck } from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Input, Select } from '../../components/ui/FormFields';
import { Modal } from '../../components/ui/Modal';
import { BasePageProps } from '../../types';

export const Login: React.FC<BasePageProps> = ({ flash, errors, csrfToken }) => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // State untuk Reset Password Modal (5 Role)
  const [showResetModal, setShowResetModal] = useState(false);
  const [resetStep, setResetStep] = useState<1 | 2>(1);
  const [resetLoading, setResetLoading] = useState(false);
  const [resetError, setResetError] = useState<string | null>(null);
  const [resetSuccess, setResetSuccess] = useState<string | null>(null);

  // Field Step 1: Verifikasi Identitas Akademik Rahasia (Rekomendasi 1: NISN/NIP + Tanggal Lahir)
  const [resetUsername, setResetUsername] = useState('');
  const [resetRole, setResetRole] = useState('siswa');
  const [resetNisnNip, setResetNisnNip] = useState('');
  const [resetBirthDate, setResetBirthDate] = useState('');

  // Field Step 2: Set Kata Sandi Baru
  const [resetToken, setResetToken] = useState('');
  const [verifiedUserData, setVerifiedUserData] = useState<{
    username: string;
    fullname: string;
    role_label: string;
  } | null>(null);
  const [newPassword, setNewPassword] = useState('');
  const [newPasswordConfirm, setNewPasswordConfirm] = useState('');

  // Periksa jika diarahkan dengan query ?forgot=1
  useEffect(() => {
    if (typeof window !== 'undefined') {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('forgot') === '1') {
        handleOpenReset();
      }
    }
  }, []);

  const handleSubmit = () => {
    setIsSubmitting(true);
  };

  const handleOpenReset = () => {
    setResetUsername(username);
    setResetRole('siswa');
    setResetNisnNip('');
    setResetBirthDate('');
    setResetError(null);
    setResetSuccess(null);
    setResetStep(1);
    setResetToken('');
    setVerifiedUserData(null);
    setNewPassword('');
    setNewPasswordConfirm('');
    setShowResetModal(true);
  };

  const handleCloseReset = () => {
    if (resetLoading) return;
    setShowResetModal(false);
    setResetError(null);
    setResetSuccess(null);
  };

  // Step 1: Kirim request verifikasi akun ke backend (Ketat: NISN/NIP + Tanggal Lahir)
  const handleVerifyIdentity = async (e: React.FormEvent) => {
    e.preventDefault();
    setResetLoading(true);
    setResetError(null);

    const token = csrfToken || (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

    try {
      const response = await fetch('/forgot-password/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({
          username: resetUsername,
          role_name: resetRole,
          nisn_nip: resetNisnNip,
          birth_date: resetBirthDate,
        }),
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        setResetError(data.message || 'Verifikasi data akun tidak sesuai. Silakan periksa kembali data Anda.');
        setResetLoading(false);
        return;
      }

      setResetToken(data.token);
      setVerifiedUserData({
        username: data.username,
        fullname: data.fullname,
        role_label: data.role_label,
      });
      setResetStep(2);
      setResetError(null);
    } catch {
      setResetError('Gagal menghubungi server. Pastikan koneksi server lokal aktif.');
    } finally {
      setResetLoading(false);
    }
  };

  // Step 2: Simpan kata sandi baru
  const handleSaveNewPassword = async (e: React.FormEvent) => {
    e.preventDefault();

    if (newPassword.length < 6) {
      setResetError('Kata sandi baru minimal 6 karakter.');
      return;
    }

    if (newPassword !== newPasswordConfirm) {
      setResetError('Konfirmasi kata sandi tidak cocok.');
      return;
    }

    setResetLoading(true);
    setResetError(null);

    const token = csrfToken || (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

    try {
      const response = await fetch('/forgot-password/reset', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({
          username: verifiedUserData?.username || resetUsername,
          token: resetToken,
          password: newPassword,
          password_confirmation: newPasswordConfirm,
        }),
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        setResetError(data.message || 'Gagal mengubah kata sandi.');
        setResetLoading(false);
        return;
      }

      setResetSuccess(data.message);
      setUsername(verifiedUserData?.username || resetUsername);
      setPassword('');

      setTimeout(() => {
        setShowResetModal(false);
        setResetSuccess(null);
      }, 1600);
    } catch {
      setResetError('Gagal menyimpan kata sandi. Silakan coba kembali.');
    } finally {
      setResetLoading(false);
    }
  };

  const usernameError = errors?.username?.[0];
  const generalError = flash?.error || errors?.password?.[0];

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 selection:bg-indigo-600 selection:text-white">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        {/* Brand Icon */}
        <div className="flex justify-center">
          <div className="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
            <GraduationCap className="w-6 h-6" />
          </div>
        </div>
        <h2 className="mt-4 text-center text-xl font-bold text-slate-900 tracking-tight">
          School Class Progress
        </h2>
        <p className="mt-1 text-center text-xs text-slate-500">
          Sistem Operasional Pembelajaran Blok Teaching Factory SMK
        </p>
      </div>

      <motion.div
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.2 }}
        className="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4"
      >
        <div className="bg-white py-7 px-6 sm:px-8 rounded-xl shadow-xs border border-slate-200/80">
          {/* Flash Alert */}
          {generalError && (
            <div className="mb-5 p-3.5 rounded-lg bg-red-50 border border-red-200/80 flex items-start gap-2.5">
              <AlertCircle className="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
              <div>
                <h4 className="text-xs font-semibold text-red-900">Gagal Masuk</h4>
                <p className="text-xs text-red-700 mt-0.5">{generalError}</p>
              </div>
            </div>
          )}

          {flash?.status && (
            <div className="mb-5 p-3.5 rounded-lg bg-indigo-50 border border-indigo-200 text-xs font-semibold text-indigo-800">
              {flash.status}
            </div>
          )}

          <form method="POST" action="/login" onSubmit={handleSubmit} className="space-y-4">
            {csrfToken && <input type="hidden" name="_token" value={csrfToken} />}

            {/* Username Input */}
            <div>
              <Input
                label="Username Pengguna"
                name="username"
                type="text"
                required
                autoFocus
                autoComplete="username"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                placeholder="Masukkan username Anda"
                error={usernameError}
              />
            </div>

            {/* Password Input */}
            <div>
              <Input
                label="Kata Sandi"
                name="password"
                type="password"
                required
                autoComplete="current-password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
              />
            </div>

            {/* Remember Me & Lupa Sandi */}
            <div className="flex items-center justify-between pt-0.5">
              <label className="flex items-center cursor-pointer select-none">
                <input
                  type="checkbox"
                  name="remember"
                  checked={remember}
                  onChange={(e) => setRemember(e.target.checked)}
                  className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer"
                />
                <span className="ml-2 text-xs font-medium text-slate-600">Ingat perangkat ini</span>
              </label>

              <button
                type="button"
                onClick={handleOpenReset}
                className="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline transition-colors cursor-pointer"
              >
                Lupa Sandi?
              </button>
            </div>

            {/* Submit Button */}
            <div className="pt-1">
              <Button type="submit" variant="primary" size="lg" className="w-full h-10 font-semibold" isLoading={isSubmitting}>
                <span>Masuk ke Workstation</span>
                <ArrowRight className="w-4 h-4" />
              </Button>
            </div>
          </form>

          {/* Footer Notice */}
          <div className="mt-5 pt-4 border-t border-slate-100 text-center">
            <p className="text-[11px] text-slate-400">
              Akun guru, staf, dan siswa dikelola terpusat oleh Waka Kurikulum.
            </p>
          </div>
        </div>
      </motion.div>

      {/* MODAL RESET KATA SANDI (5 ROLE TERDAFTAR) */}
      <Modal
        isOpen={showResetModal}
        onClose={handleCloseReset}
        title={resetStep === 1 ? 'Pemulihan Kata Sandi' : 'Buat Kata Sandi Baru'}
        description={
          resetStep === 1
            ? 'Layanan verifikasi mandiri untuk Guru, Wali Kelas, Guru BK, Siswa, dan Orang Tua.'
            : 'Masukkan kata sandi baru untuk akun yang telah terverifikasi.'
        }
      >
        {/* Step 1: Verifikasi Akun */}
        {resetStep === 1 && (
          <form onSubmit={handleVerifyIdentity} className="space-y-4">
            <div className="p-3 bg-indigo-50/70 border border-indigo-100 rounded-xl flex items-start gap-2.5 text-xs text-indigo-900">
              <ShieldCheck className="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" />
              <div>
                <p className="font-semibold">Verifikasi Identitas Akademik Terproteksi</p>
                <p className="text-slate-600 mt-0.5">
                  Verifikasi memerlukan Nomor Induk (NISN/NIP) &amp; Tanggal Lahir terdaftar. Sistem membatasi maks. 3 percobaan per 15 menit. Akun Administrator (Waka) dikelola langsung oleh server pusat.
                </p>
              </div>
            </div>

            {resetError && (
              <div className="p-3 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2 text-xs text-red-700">
                <AlertCircle className="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
                <span>{resetError}</span>
              </div>
            )}

            <div>
              <Input
                label="Username Akun"
                name="reset_username"
                type="text"
                required
                value={resetUsername}
                onChange={(e) => setResetUsername(e.target.value)}
                placeholder="Contoh: siswa1, guru1, ortu1"
                helperText="Masukkan username yang Anda gunakan untuk masuk."
              />
            </div>

            <div>
              <Select
                label="Peran Akun Terdaftar"
                name="reset_role"
                value={resetRole}
                onChange={(e) => setResetRole(e.target.value)}
                required
              >
                <option value="siswa">Siswa</option>
                <option value="guru">Guru Pengajar</option>
                <option value="wali_kelas">Wali Kelas</option>
                <option value="guru_bk">Guru Bimbingan Konseling (BK)</option>
                <option value="orang_tua">Orang Tua / Wali Siswa</option>
              </Select>
            </div>

            <div>
              <Input
                label={
                  resetRole === 'siswa'
                    ? 'NISN Siswa (10 Digit)'
                    : resetRole === 'orang_tua'
                    ? 'NISN Siswa Terdaftar (Anak)'
                    : 'NIP / NUPTK Terdaftar'
                }
                name="reset_nisn_nip"
                type="text"
                required
                value={resetNisnNip}
                onChange={(e) => setResetNisnNip(e.target.value)}
                placeholder={
                  resetRole === 'siswa' || resetRole === 'orang_tua'
                    ? 'Contoh: 0071234567'
                    : 'Contoh: 198001012005011001'
                }
                helperText={
                  resetRole === 'orang_tua'
                    ? 'NISN siswa/anak yang terhubung dengan akun Anda.'
                    : resetRole === 'siswa'
                    ? 'Nomor Induk Siswa Nasional resmi terdaftar di Dapodik.'
                    : 'Nomor Induk Pegawai / NUPTK resmi terdaftar di kepegawaian.'
                }
              />
            </div>

            <div>
              <Input
                label={
                  resetRole === 'orang_tua'
                    ? 'Tanggal Lahir Siswa (Anak Terdaftar)'
                    : 'Tanggal Lahir Terdaftar'
                }
                name="reset_birth_date"
                type="date"
                required
                value={resetBirthDate}
                onChange={(e) => setResetBirthDate(e.target.value)}
                helperText={
                  resetRole === 'orang_tua'
                    ? 'Pilih tanggal lahir anak sesuai data Dapodik.'
                    : 'Pilih tanggal lahir Anda sesuai data terdaftar sekolah.'
                }
              />
            </div>

            <div className="pt-2 flex justify-end gap-2">
              <Button type="button" variant="outline" size="sm" onClick={handleCloseReset} disabled={resetLoading}>
                Batal
              </Button>
              <Button type="submit" variant="primary" size="sm" isLoading={resetLoading}>
                Verifikasi Identitas
              </Button>
            </div>
          </form>
        )}

        {/* Step 2: Buat Kata Sandi Baru */}
        {resetStep === 2 && (
          <form onSubmit={handleSaveNewPassword} className="space-y-4">
            <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-2.5 text-xs text-emerald-900">
              <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
              <div>
                <p className="font-bold">Identitas Terverifikasi</p>
                <p className="text-emerald-800 mt-0.5">
                  Akun: <strong>{verifiedUserData?.username}</strong> ({verifiedUserData?.fullname})
                  <br />
                  Peran: <span className="font-semibold text-emerald-950">{verifiedUserData?.role_label}</span>
                </p>
              </div>
            </div>

            {resetError && (
              <div className="p-3 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2 text-xs text-red-700">
                <AlertCircle className="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
                <span>{resetError}</span>
              </div>
            )}

            {resetSuccess && (
              <div className="p-3 bg-emerald-100 border border-emerald-300 rounded-xl flex items-center gap-2 text-xs text-emerald-900 font-semibold">
                <CheckCircle2 className="w-4 h-4 text-emerald-700 shrink-0" />
                <span>{resetSuccess} Mengalihkan ke login...</span>
              </div>
            )}

            <div>
              <Input
                label="Kata Sandi Baru"
                name="new_password"
                type="password"
                required
                autoFocus
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                placeholder="Minimal 6 karakter"
              />
            </div>

            <div>
              <Input
                label="Konfirmasi Kata Sandi Baru"
                name="new_password_confirm"
                type="password"
                required
                value={newPasswordConfirm}
                onChange={(e) => setNewPasswordConfirm(e.target.value)}
                placeholder="Ulangi kata sandi baru"
              />
            </div>

            <div className="pt-2 flex justify-between items-center">
              <button
                type="button"
                onClick={() => {
                  setResetStep(1);
                  setResetError(null);
                }}
                disabled={resetLoading}
                className="text-xs font-semibold text-slate-500 hover:text-slate-800 underline cursor-pointer"
              >
                &larr; Ubah Data Verifikasi
              </button>

              <div className="flex gap-2">
                <Button type="button" variant="outline" size="sm" onClick={handleCloseReset} disabled={resetLoading}>
                  Batal
                </Button>
                <Button type="submit" variant="primary" size="sm" isLoading={resetLoading} disabled={!!resetSuccess}>
                  <KeyRound className="w-3.5 h-3.5 mr-1" />
                  Simpan Kata Sandi
                </Button>
              </div>
            </div>
          </form>
        )}
      </Modal>
    </div>
  );
};
