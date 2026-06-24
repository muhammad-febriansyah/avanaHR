import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { IsCurrentUrlFn } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({
    items = [],
    label,
}: {
    items: NavItem[];
    label?: string;
}) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-3 py-0">
            {label && (
                <SidebarGroupLabel className="h-auto px-3 pt-2.5 pb-1 text-[10.5px] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
                    {label}
                </SidebarGroupLabel>
            )}
            <SidebarMenu className="gap-[3px]">
                {items.map((item) =>
                    item.children?.length ? (
                        <CollapsibleNavItem
                            key={item.title}
                            item={item}
                            isCurrentUrl={isCurrentUrl}
                        />
                    ) : (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isCurrentUrl(item.href)}
                                tooltip={{ children: item.title }}
                                className="h-[42px] gap-3 rounded-[9px] px-3 text-[13.5px] font-medium text-sidebar-foreground data-[active=true]:bg-[rgba(47,84,201,0.09)] data-[active=true]:font-semibold data-[active=true]:text-primary [&>svg]:size-[18px]"
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ),
                )}
            </SidebarMenu>
        </SidebarGroup>
    );
}

function CollapsibleNavItem({
    item,
    isCurrentUrl,
}: {
    item: NavItem;
    isCurrentUrl: IsCurrentUrlFn;
}) {
    const children = item.children ?? [];
    const hasActiveChild = children.some((child) => isCurrentUrl(child.href));

    return (
        <Collapsible
            asChild
            defaultOpen={hasActiveChild}
            className="group/collapsible"
        >
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton
                        isActive={hasActiveChild}
                        tooltip={{ children: item.title }}
                        className="h-[42px] gap-3 rounded-[9px] px-3 text-[13.5px] font-medium text-sidebar-foreground data-[active=true]:bg-[rgba(47,84,201,0.09)] data-[active=true]:font-semibold data-[active=true]:text-primary [&>svg]:size-[18px]"
                    >
                        {item.icon && <item.icon />}
                        <span>{item.title}</span>
                        <ChevronRight className="ml-auto size-4 shrink-0 text-muted-foreground transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub className="mt-0.5 mr-0 gap-[3px] border-l-sidebar-border pl-3">
                        {children.map((child) => (
                            <SidebarMenuSubItem key={child.title}>
                                <SidebarMenuSubButton
                                    asChild
                                    isActive={isCurrentUrl(child.href)}
                                    className="h-[34px] rounded-[8px] px-3 text-[12.5px] text-sidebar-foreground/80 data-[active=true]:bg-[rgba(47,84,201,0.09)] data-[active=true]:font-semibold data-[active=true]:text-primary"
                                >
                                    <Link href={child.href} prefetch>
                                        <span>{child.title}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}
