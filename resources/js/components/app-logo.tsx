// resources/js/components/app-logo.tsx

export default function AppLogo() {
    return (
        <>
            {/* 1. Increased container size from size-8 to size-12 (48px) */}
            <div className="flex aspect-square size-10 items-center justify-center rounded-md overflow-hidden">
                <img 
                    src="/logo.png" 
                    alt="Logo" 
                    /* 2. Ensured image fills the new larger container */
                    className="size-full object-contain" 
                />
            </div>
            
            <div className="ml-3 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-bold text-base">
                   Lemif International Hotel and Resort
                </span>
            </div>
        </>
    );
}