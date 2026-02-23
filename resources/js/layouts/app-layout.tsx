// resources/js/layouts/AppLayout.tsx
import React, { type ReactNode } from 'react';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import Footer from '@/components/Footer';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

const AppLayout = ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        <div className="flex flex-col min-h-screen">
            <main className="flex-grow">{children}</main>
            <Footer />
        </div>
    </AppLayoutTemplate>
);

export default AppLayout;