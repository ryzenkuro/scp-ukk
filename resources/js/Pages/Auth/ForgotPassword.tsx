import React, { useState, useEffect } from 'react';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { School, Mail, KeyRound, Lock, ArrowLeft, RefreshCw, CheckCircle2, AlertCircle } from 'lucide-react';

interface ForgotPasswordProps {
    status?: string;
    error?: string;
    demoOtp?: string;
    activeEmail?: string;
    currentStep?: number;
}

export default function ForgotPassword({
    status,
    error,
    demoOtp,
    activeEmail = '',
    currentStep = 1,
}: ForgotPasswordProps) {
    const { flash } = usePage<any>().props;
    const [step, setStep] = useState<number>(currentStep);
    const [email, setEmail] = useState<string>(activeEmail);
    const [countdown, setCountdown] = useState<number>(60);
    const [canResend, setCanResend] = useState<boolean>(false);

    // Sync step from server session when it changes
    useEffect(() => {
        if (currentStep) {
            setStep(currentStep);
        }
    }, [currentStep]);

    useEffect(() => {
        if (activeEmail) {
            setEmail(activeEmail);
        }
    }, [activeEmail]);

    // Timer countdown untuk tombol kirim ulang OTP
    useEffect(() => {
        let timer: any;
        if (step === 2 && countdown > 0) {
            timer = setInterval(() => {
                setCountdown((prev) => prev - 1);
            }, 1000);
        } else if (countdown === 0) {
            setCanResend(true);
        }
        return () => clearInterval(timer);
    }, [step, countdown]);

    // Form 1: Input Email
    const emailForm = useForm({
        email: email,
    });

    // Form 2: Input OTP
    const otpForm = useForm({
        email: email,
        otp: demoOtp || '',
    });

    // Form 3: Input Password Baru
    const resetForm = useForm({
        email: email,
        otp: '',
        password: '',
        password_confirmation: '',
    });

    // Submit Langkah 1: Kirim OTP
    const handleSendEmail = (e: React.FormEvent) => {
        e.preventDefault();
        emailForm.post('/forgot-password/send-otp', {
            preserveScroll: true,
            onSuccess: () => {
                setEmail(emailForm.data.email);
                otpForm.setData('email', emailForm.data.email);
                resetForm.setData('email', emailForm.data.email);
                setStep(2);
                setCountdown(60);
                setCanResend(false);
            },
        });
    };

    // Submit Langkah 2: Verifikasi OTP
    const handleVerifyOtp = (e: React.FormEvent) => {
        e.preventDefault();
        otpForm.setData('email', email);
        otpForm.post('/forgot-password/verify-otp', {
            preserveScroll: true,
            onSuccess: () => {
                resetForm.setData('email', email);
                resetForm.setData('otp', otpForm.data.otp);
                setStep(3);
            },
        });
    };

    // Kirim Ulang OTP
    const handleResendOtp = () => {
        if (!canResend) return;
        router.post('/forgot-password/send-otp', { email }, {
            preserveScroll: true,
            onSuccess: () => {
                setCountdown(60);
                setCanResend(false);
            },
        });
    };

    // Submit Langkah 3: Simpan Password Baru
    const handleResetPassword = (e: React.FormEvent) => {
        e.preventDefault();
        resetForm.setData('email', email);
        resetForm.post('/forgot-password/reset', {
            preserveScroll: true,
        });
    };

    const displayStatus = flash?.status || status;
    const displayError = flash?.error || error;

    return (
        <section className="bg-muted/40 min-h-screen flex items-center justify-center px-4 py-12">
            <Head title="Pemulihan Kata Sandi" />

            <div className="w-full max-w-md">
                <Card className="shadow-sm border-border bg-card">
                    <CardHeader className="text-center pb-2">
                        {/* School Logo */}
                        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-card border border-border/80 p-2 shadow-sm mb-2">
                            <img src="/images/logosmk.png" alt="Logo SMK N 1 Bantul" className="h-full w-full object-contain" />
                        </div>
                        <CardTitle className="text-xl font-bold tracking-tight">
                            SMK NEGERI 1 BANTUL
                        </CardTitle>
                        <CardDescription className="text-xs text-muted-foreground mt-0.5">
                            {step === 1 && 'Pemulihan Kata Sandi • Masukkan Email Akun'}
                            {step === 2 && 'Verifikasi OTP • Masukkan Kode 6 Digit'}
                            {step === 3 && 'Atur Ulang Kata Sandi Baru'}
                        </CardDescription>

                        {/* Step Indicator */}
                        <div className="flex items-center justify-center gap-2 pt-3">
                            <span className={`h-2 w-8 rounded-full transition-colors ${step >= 1 ? 'bg-primary' : 'bg-muted'}`} />
                            <span className={`h-2 w-8 rounded-full transition-colors ${step >= 2 ? 'bg-primary' : 'bg-muted'}`} />
                            <span className={`h-2 w-8 rounded-full transition-colors ${step >= 3 ? 'bg-primary' : 'bg-muted'}`} />
                        </div>
                    </CardHeader>

                    <CardContent className="pt-4 space-y-4">
                        {/* Feedback Alerts */}
                        {displayStatus && (
                            <div className="rounded-md bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-700 dark:text-emerald-400 font-medium flex items-start gap-2">
                                <CheckCircle2 className="h-4 w-4 shrink-0 mt-0.5" />
                                <span>{displayStatus}</span>
                            </div>
                        )}

                        {displayError && (
                            <div className="rounded-md bg-destructive/10 border border-destructive/20 p-3 text-xs text-destructive font-medium flex items-start gap-2">
                                <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
                                <span>{displayError}</span>
                            </div>
                        )}

                        {/* Demo OTP Helper (Hanya tampil di Local/Debug untuk mempermudah UKK & pengujian) */}
                        {demoOtp && step === 2 && (
                            <div className="rounded-md bg-amber-500/10 border border-amber-500/30 p-2.5 text-xs text-amber-800 dark:text-amber-300">
                                <p className="font-semibold flex items-center gap-1.5">
                                    <span>🔑</span> Mode Demo / Pengujian Lokal:
                                </p>
                                <p className="mt-0.5">
                                    Kode OTP Anda: <strong className="font-mono text-sm tracking-widest text-foreground">{demoOtp}</strong>
                                </p>
                            </div>
                        )}

                        {/* ================= STEP 1: INPUT EMAIL ================= */}
                        {step === 1 && (
                            <form onSubmit={handleSendEmail} className="space-y-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="email">Email Terdaftar di Akun</Label>
                                    <div className="relative">
                                        <Mail className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="email"
                                            type="email"
                                            value={emailForm.data.email}
                                            placeholder="Contoh: namaanda@gmail.com"
                                            onChange={(e) => {
                                                emailForm.setData('email', e.target.value);
                                                setEmail(e.target.value);
                                            }}
                                            className="pl-9"
                                            required
                                            autoFocus
                                        />
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">
                                        Masukkan alamat email yang telah Anda daftarkan pada akun sekolah saat aktivasi atau data Waka.
                                    </p>
                                    {emailForm.errors.email && (
                                        <p className="text-xs text-destructive">{emailForm.errors.email}</p>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={emailForm.processing}
                                >
                                    {emailForm.processing ? 'Memverifikasi Email...' : 'Kirim Kode OTP ke Email'}
                                </Button>
                            </form>
                        )}

                        {/* ================= STEP 2: INPUT KODE OTP ================= */}
                        {step === 2 && (
                            <form onSubmit={handleVerifyOtp} className="space-y-4">
                                <div className="space-y-1.5 text-center">
                                    <Label htmlFor="otp">Masukkan 6 Digit Kode OTP</Label>
                                    <p className="text-xs text-muted-foreground">
                                        Kode dikirim ke <strong className="text-foreground">{email}</strong>. Berlaku 10 menit.
                                    </p>
                                    <div className="pt-2">
                                        <Input
                                            id="otp"
                                            type="text"
                                            inputMode="numeric"
                                            pattern="[0-9]*"
                                            maxLength={6}
                                            value={otpForm.data.otp}
                                            placeholder="000000"
                                            onChange={(e) => otpForm.setData('otp', e.target.value.replace(/\D/g, ''))}
                                            className="text-center font-mono text-2xl font-bold tracking-widest h-12"
                                            required
                                            autoFocus
                                        />
                                    </div>
                                    {otpForm.errors.otp && (
                                        <p className="text-xs text-destructive text-center mt-1">{otpForm.errors.otp}</p>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={otpForm.processing || otpForm.data.otp.length !== 6}
                                >
                                    {otpForm.processing ? 'Memverifikasi...' : 'Verifikasi Kode OTP'}
                                </Button>

                                <div className="flex items-center justify-between pt-1 text-xs">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setStep(1);
                                            otpForm.setData('otp', '');
                                        }}
                                        className="text-muted-foreground hover:underline"
                                    >
                                        Ganti Email
                                    </button>

                                    {canResend ? (
                                        <button
                                            type="button"
                                            onClick={handleResendOtp}
                                            className="text-primary hover:underline font-semibold flex items-center gap-1"
                                        >
                                            <RefreshCw className="h-3 w-3" />
                                            Kirim Ulang Kode
                                        </button>
                                    ) : (
                                        <span className="text-muted-foreground font-mono">
                                            Kirim ulang dalam {countdown}s
                                        </span>
                                    )}
                                </div>
                            </form>
                        )}

                        {/* ================= STEP 3: INPUT PASSWORD BARU ================= */}
                        {step === 3 && (
                            <form onSubmit={handleResetPassword} className="space-y-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="password">Kata Sandi Baru</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={resetForm.data.password}
                                        placeholder="Minimal 8 karakter"
                                        onChange={(e) => resetForm.setData('password', e.target.value)}
                                        required
                                        autoFocus
                                    />
                                    {resetForm.errors.password && (
                                        <p className="text-xs text-destructive">{resetForm.errors.password}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="password_confirmation">Ulangi Kata Sandi Baru</Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        value={resetForm.data.password_confirmation}
                                        placeholder="Konfirmasi kata sandi baru"
                                        onChange={(e) => resetForm.setData('password_confirmation', e.target.value)}
                                        required
                                    />
                                    {resetForm.errors.password_confirmation && (
                                        <p className="text-xs text-destructive">{resetForm.errors.password_confirmation}</p>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full mt-2"
                                    disabled={resetForm.processing}
                                >
                                    {resetForm.processing ? 'Menyimpan Kata Sandi...' : 'Simpan Kata Sandi & Masuk'}
                                </Button>
                            </form>
                        )}
                    </CardContent>

                    <CardFooter className="pt-2 pb-6 border-t border-border flex justify-center">
                        <Link
                            href="/login"
                            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium transition-colors"
                        >
                            <ArrowLeft className="h-3.5 w-3.5" />
                            <span>Kembali ke Halaman Masuk (Login)</span>
                        </Link>
                    </CardFooter>
                </Card>

                <div className="text-center text-[11px] text-muted-foreground mt-4">
                    &copy; 2026 SMK Negeri 1 Bantul &bull; School Class Progress RPL
                </div>
            </div>
        </section>
    );
}
