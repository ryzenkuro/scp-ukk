import React from 'react';
import { motion, HTMLMotionProps } from 'motion/react';
import { Loader2 } from 'lucide-react';

export interface ButtonProps extends Omit<HTMLMotionProps<'button'>, 'children'> {
  variant?: 'primary' | 'secondary' | 'destructive' | 'danger' | 'outline' | 'ghost' | 'link';
  size?: 'sm' | 'md' | 'lg' | 'icon';
  icon?: React.ComponentType<{ className?: string }>;
  isLoading?: boolean;
  children?: React.ReactNode;
}

export const Button: React.FC<ButtonProps> = ({
  variant = 'primary',
  size = 'md',
  icon: Icon,
  isLoading = false,
  className = '',
  disabled,
  children,
  ...props
}) => {
  const baseClasses =
    'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-150 outline-none select-none focus-visible:ring-2 focus-visible:ring-offset-1 disabled:opacity-50 disabled:pointer-events-none cursor-pointer tracking-tight';

  const sizeClasses = {
    sm: 'h-8 px-3 text-xs gap-1.5',
    md: 'h-9 px-3.5 text-xs sm:text-sm gap-2',
    lg: 'h-10 px-4 text-sm gap-2',
    icon: 'h-9 w-9 p-0',
  }[size];

  const variantClasses = {
    primary:
      'bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs focus-visible:ring-indigo-500 focus-visible:ring-offset-2 border border-transparent',
    secondary:
      'bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200/80 shadow-2xs focus-visible:ring-slate-400',
    destructive:
      'bg-red-600 hover:bg-red-700 text-white shadow-xs focus-visible:ring-red-500 focus-visible:ring-offset-2 border border-transparent',
    danger:
      'bg-red-600 hover:bg-red-700 text-white shadow-xs focus-visible:ring-red-500 focus-visible:ring-offset-2 border border-transparent',
    outline:
      'border border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-2xs focus-visible:ring-slate-400',
    ghost:
      'text-slate-700 hover:bg-slate-100/80 hover:text-slate-900 focus-visible:ring-slate-400 border border-transparent',
    link:
      'text-indigo-600 hover:text-indigo-700 hover:underline p-0 h-auto border-0 shadow-none focus-visible:ring-0',
  }[variant];

  return (
    <motion.button
      whileTap={{ scale: 0.985 }}
      disabled={disabled || isLoading}
      className={`${baseClasses} ${sizeClasses} ${variantClasses} ${className}`}
      {...props}
    >
      {isLoading ? (
        <>
          <Loader2 className="w-3.5 h-3.5 animate-spin shrink-0" />
          <span>Memproses...</span>
        </>
      ) : (
        <>
          {Icon && <Icon className="w-3.5 h-3.5 shrink-0" />}
          {children}
        </>
      )}
    </motion.button>
  );
};
