import { SidebarInset } from '@/components/ui/sidebar';
import * as React from 'react';
import { useLayout } from '@/contexts/LayoutContext';

interface AppContentProps extends React.ComponentProps<'main'> {
    variant?: 'header' | 'sidebar';
}

// export function AppContent({ variant = 'header', children, ...props }: AppContentProps) {
//     if (variant === 'sidebar') {
//         return <SidebarInset {...props}>{children}</SidebarInset>;
//     }

//     return (
//         <main className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl" {...props}>
//             {children}
//         </main>
//     );
// }

export function AppContent({ variant = 'header', children, ...props }: AppContentProps) {
    const { position } = useLayout();
    console.log('position' , position);
    

    if (variant === 'sidebar') {
        return (
            <SidebarInset {...props}>
                <div dir={position === 'right' ? 'rtl' : 'ltr'}>
                    {children}
                </div>
            </SidebarInset>
        );
    }

    return (
        <main className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl" {...props}>
            <div dir={position === 'right' ? 'rtl' : 'ltr'}>
                {children}
            </div>
        </main>
    );
}
