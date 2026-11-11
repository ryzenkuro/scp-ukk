import { ImgHTMLAttributes } from 'react';

export default function ApplicationLogo({ className = 'h-9 w-auto', ...props }: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/images/logosmk.png"
            alt="Logo SMK Negeri 1 Bantul"
            className={className}
            {...props}
        />
    );
}
