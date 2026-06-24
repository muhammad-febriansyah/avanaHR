import { usePage } from '@inertiajs/react';
import { Bell, ChevronDown, Search } from 'lucide-react';
import { toast } from 'sonner';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';

// TODO: derive from the authenticated user's tenant once HR Core / tenancy ships.
const COMPANY_NAME = 'PT Nusantara Jaya';

export function AppSidebarHeader() {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    return (
        <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-4 border-b border-sidebar-border/60 bg-card px-4 md:px-6">
            <SidebarTrigger className="-ml-1 size-9 shrink-0" />

            {/* Search */}
            <div className="relative hidden max-w-md flex-1 sm:block">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    aria-label="Cari"
                    placeholder="Cari karyawan, dokumen, menu…"
                    className="h-10 bg-muted pl-10 focus-visible:bg-background"
                />
            </div>

            <div className="flex-1" />

            {/* Notifications */}
            <button
                type="button"
                aria-label="Notifikasi"
                onClick={() => toast.info('Tidak ada notifikasi baru')}
                className="relative flex size-10 items-center justify-center rounded-lg border border-border bg-background text-foreground transition-colors hover:bg-muted"
            >
                <Bell className="size-[18px]" />
                <span className="absolute top-1.5 right-1.5 size-[7px] rounded-full border-[1.5px] border-background bg-destructive" />
            </button>

            <div className="h-7 w-px bg-border" />

            {/* User */}
            {auth.user && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            data-test="topbar-user-button"
                            className="flex items-center gap-2.5 rounded-lg p-1 pr-2 transition-colors hover:bg-muted data-[state=open]:bg-muted"
                        >
                            <span className="flex size-9 items-center justify-center rounded-[9px] bg-[linear-gradient(135deg,#2F54C9,#6E9BE6)] text-sm font-semibold text-white">
                                {getInitials(auth.user.name)}
                            </span>
                            <span className="hidden text-left leading-tight md:block">
                                <span className="block text-[13px] font-semibold text-navy">
                                    {auth.user.name}
                                </span>
                                <span className="block text-[11.5px] text-muted-foreground">
                                    {COMPANY_NAME}
                                </span>
                            </span>
                            <ChevronDown className="hidden size-4 text-muted-foreground md:block" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="min-w-56 rounded-lg"
                        align="end"
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </header>
    );
}
