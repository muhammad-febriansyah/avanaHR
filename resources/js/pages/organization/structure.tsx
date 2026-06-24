import { Head } from '@inertiajs/react';
import { Building2, ChevronRight, Network, Users } from 'lucide-react';
import { useState } from 'react';
import PageHeader from '@/components/page-header';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type OrgNode = {
    id: number;
    type: 'company' | 'department';
    name: string;
    code: string;
    headcount: number;
    children: OrgNode[];
};

type StructureProps = {
    tree: OrgNode[];
};

function TreeNode({ node, depth }: { node: OrgNode; depth: number }) {
    const [open, setOpen] = useState(depth < 1);
    const hasChildren = node.children.length > 0;
    const isCompany = node.type === 'company';

    return (
        <li>
            <div
                className="flex items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-muted/60"
                style={{ paddingLeft: `${depth * 20 + 8}px` }}
            >
                <button
                    type="button"
                    onClick={() => hasChildren && setOpen((value) => !value)}
                    className={cn(
                        'flex size-5 shrink-0 items-center justify-center rounded text-muted-foreground',
                        hasChildren
                            ? 'hover:text-foreground'
                            : 'invisible cursor-default',
                    )}
                    aria-label={open ? 'Tutup' : 'Buka'}
                >
                    <ChevronRight
                        className={cn(
                            'size-4 transition-transform',
                            open && 'rotate-90',
                        )}
                    />
                </button>

                <span
                    className={cn(
                        'flex size-8 shrink-0 items-center justify-center rounded-lg',
                        isCompany
                            ? 'bg-[linear-gradient(135deg,#2F54C9,#6E9BE6)] text-white'
                            : 'bg-primary/10 text-primary',
                    )}
                >
                    {isCompany ? (
                        <Building2 className="size-[18px]" />
                    ) : (
                        <Network className="size-[18px]" />
                    )}
                </span>

                <div className="min-w-0 flex-1">
                    <p
                        className={cn(
                            'truncate text-sm text-navy',
                            isCompany ? 'font-semibold' : 'font-medium',
                        )}
                    >
                        {node.name}
                    </p>
                    <p className="truncate text-xs text-muted-foreground">
                        {node.code}
                    </p>
                </div>

                <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-card px-2.5 py-1 text-xs font-medium text-muted-foreground">
                    <Users className="size-3.5" />
                    <span className="tabular-nums text-foreground">
                        {node.headcount}
                    </span>
                </span>
            </div>

            {hasChildren && open && (
                <ul>
                    {node.children.map((child) => (
                        <TreeNode
                            key={`${child.type}-${child.id}`}
                            node={child}
                            depth={depth + 1}
                        />
                    ))}
                </ul>
            )}
        </li>
    );
}

export default function OrganizationStructure({ tree }: StructureProps) {
    return (
        <>
            <Head title="Struktur Organisasi" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Struktur Organisasi"
                    description="Hierarki perusahaan, departemen, dan jumlah karyawan aktif."
                />

                <Card>
                    <CardContent>
                        {tree.length === 0 ? (
                            <div className="flex flex-col items-center justify-center gap-3 py-12 text-center">
                                <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                    <Network className="size-6" />
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Belum ada struktur organisasi
                                </p>
                            </div>
                        ) : (
                            <ul>
                                {tree.map((node) => (
                                    <TreeNode
                                        key={`${node.type}-${node.id}`}
                                        node={node}
                                        depth={0}
                                    />
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
