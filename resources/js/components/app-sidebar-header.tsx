import { router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck, ChevronDown, Search } from 'lucide-react';
import notifications from '@/actions/App/Http/Controllers/NotificationController';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';

type NotificationItem = {
    id: number;
    title: string;
    read: boolean;
    at: string | null;
};

export function AppSidebarHeader() {
    const props = usePage().props;
    const auth = props.auth;
    const org = (
        props as unknown as {
            org?: { name: string; logo: string | null };
        }
    ).org;
    const notif = (
        props as unknown as {
            notifications?: { unread: number; items: NotificationItem[] };
        }
    ).notifications ?? { unread: 0, items: [] };
    const getInitials = useInitials();

    return (
        <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-4 border-b border-sidebar-border/60 bg-card px-4 md:px-6">
            <SidebarTrigger className="-ml-1 size-9 shrink-0" />

            {/* Tenant logo */}
            {org?.logo && (
                <img
                    src={org.logo}
                    alt={org.name}
                    className="hidden h-8 w-auto max-w-[120px] object-contain sm:block"
                />
            )}

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
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button
                        type="button"
                        aria-label="Notifikasi"
                        className="relative flex size-10 items-center justify-center rounded-lg border border-border bg-background text-foreground transition-colors hover:bg-muted"
                    >
                        <Bell className="size-[18px]" />
                        {notif.unread > 0 && (
                            <span className="absolute -top-1 -right-1 flex min-w-[18px] items-center justify-center rounded-full border-[1.5px] border-background bg-destructive px-1 text-[10px] font-semibold text-white">
                                {notif.unread > 9 ? '9+' : notif.unread}
                            </span>
                        )}
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-80 p-0">
                    <div className="flex items-center justify-between border-b border-border px-3 py-2">
                        <span className="text-sm font-semibold text-navy">
                            Notifikasi
                        </span>
                        {notif.unread > 0 && (
                            <button
                                type="button"
                                onClick={() =>
                                    router.post(
                                        notifications.markAllRead.url(),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                                className="flex items-center gap-1 text-xs text-primary hover:underline"
                            >
                                <CheckCheck className="size-3.5" />
                                Tandai dibaca
                            </button>
                        )}
                    </div>
                    <div className="max-h-80 overflow-y-auto">
                        {notif.items.length === 0 ? (
                            <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                                Tidak ada notifikasi
                            </p>
                        ) : (
                            notif.items.map((item) => (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() =>
                                        router.post(
                                            notifications.markRead.url(item.id),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                    className={`flex w-full flex-col gap-0.5 border-b border-border/50 px-3 py-2 text-left transition-colors hover:bg-muted ${item.read ? 'opacity-60' : 'bg-primary/5'}`}
                                >
                                    <span className="text-sm text-navy">
                                        {item.title}
                                    </span>
                                    {item.at && (
                                        <span className="text-xs text-muted-foreground">
                                            {item.at}
                                        </span>
                                    )}
                                </button>
                            ))
                        )}
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

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
                                    {org?.name ?? 'AvanaHR'}
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
