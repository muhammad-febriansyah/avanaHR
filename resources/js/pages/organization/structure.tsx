import { Head, router } from '@inertiajs/react';
import { Building2, Minus, Network, Plus, Users } from 'lucide-react';
import { createContext, useContext, useState } from 'react';
import type { DragEvent } from 'react';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
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

type DragState = {
    draggingId: number | null;
    setDraggingId: (id: number | null) => void;
    onDropOn: (target: OrgNode) => void;
};

const DragContext = createContext<DragState | null>(null);

function useDrag(): DragState {
    const context = useContext(DragContext);

    if (!context) {
        throw new Error('useDrag must be used within a DragContext provider');
    }

    return context;
}

function NodeBox({ node }: { node: OrgNode }) {
    const [open, setOpen] = useState(true);
    const [isDragOver, setIsDragOver] = useState(false);
    const drag = useDrag();
    const hasChildren = node.children.length > 0;
    const isCompany = node.type === 'company';
    const isDepartment = node.type === 'department';

    // A department dragged onto itself or onto its current dragging source is a no-op.
    const isDragSource = isDepartment && drag.draggingId === node.id;
    // Anything is a valid drop target while a department is being dragged,
    // except the dragged node itself (server still guards cross-company/cycles).
    const canDrop = drag.draggingId !== null && drag.draggingId !== node.id;

    function handleDragStart(event: DragEvent<HTMLDivElement>) {
        if (!isDepartment) {
            return;
        }

        event.stopPropagation();
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(node.id));
        drag.setDraggingId(node.id);
    }

    function handleDragEnd() {
        drag.setDraggingId(null);
        setIsDragOver(false);
    }

    function handleDragOver(event: DragEvent<HTMLDivElement>) {
        if (!canDrop) {
            return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }

    function handleDragEnter(event: DragEvent<HTMLDivElement>) {
        if (!canDrop) {
            return;
        }

        event.preventDefault();
        setIsDragOver(true);
    }

    function handleDragLeave() {
        setIsDragOver(false);
    }

    function handleDrop(event: DragEvent<HTMLDivElement>) {
        if (!canDrop) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        setIsDragOver(false);
        drag.onDropOn(node);
    }

    return (
        <li>
            <div className="org-node">
                <div
                    draggable={isDepartment}
                    onDragStart={handleDragStart}
                    onDragEnd={handleDragEnd}
                    onDragOver={handleDragOver}
                    onDragEnter={handleDragEnter}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    aria-grabbed={isDragSource}
                    className={cn(
                        'relative flex w-56 flex-col items-center gap-2 rounded-xl border bg-card px-4 py-4 text-center shadow-sm transition-all',
                        isCompany ? 'border-primary/30' : 'border-border',
                        isDepartment && 'cursor-grab active:cursor-grabbing',
                        isDragSource && 'opacity-40',
                        isDragOver &&
                            canDrop &&
                            'border-primary ring-2 ring-primary/40',
                    )}
                >
                    <span
                        className={cn(
                            'flex size-10 items-center justify-center rounded-lg',
                            isCompany
                                ? 'bg-[linear-gradient(135deg,#2F54C9,#6E9BE6)] text-white'
                                : 'bg-primary/10 text-primary',
                        )}
                    >
                        {isCompany ? (
                            <Building2 className="size-5" />
                        ) : (
                            <Network className="size-5" />
                        )}
                    </span>

                    <div className="min-w-0">
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

                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-2.5 py-1 text-xs font-medium text-muted-foreground">
                        <Users className="size-3.5" />
                        <span className="text-foreground tabular-nums">
                            {node.headcount}
                        </span>
                    </span>

                    {hasChildren && (
                        <button
                            type="button"
                            onClick={() => setOpen((value) => !value)}
                            aria-label={open ? 'Tutup' : 'Buka'}
                            className="absolute -bottom-3 left-1/2 z-10 flex size-6 -translate-x-1/2 items-center justify-center rounded-full border bg-card text-muted-foreground shadow-sm transition-colors hover:text-foreground"
                        >
                            {open ? (
                                <Minus className="size-3.5" />
                            ) : (
                                <Plus className="size-3.5" />
                            )}
                        </button>
                    )}
                </div>
            </div>

            {hasChildren && open && (
                <ul>
                    {node.children.map((child) => (
                        <NodeBox
                            key={`${child.type}-${child.id}`}
                            node={child}
                        />
                    ))}
                </ul>
            )}
        </li>
    );
}

export default function OrganizationStructure({ tree }: StructureProps) {
    const [draggingId, setDraggingId] = useState<number | null>(null);

    function handleDropOn(target: OrgNode) {
        if (draggingId === null || draggingId === target.id) {
            return;
        }

        // Dropping on a company root moves the department to the top level
        // (parent_id = null); dropping on a department nests it underneath.
        const parentId = target.type === 'company' ? null : target.id;

        router.patch(
            OrganizationController.reparent.url(draggingId),
            { parent_id: parentId },
            { preserveScroll: true },
        );

        setDraggingId(null);
    }

    const dragState: DragState = {
        draggingId,
        setDraggingId,
        onDropOn: handleDropOn,
    };

    return (
        <>
            <Head title="Struktur Organisasi" />

            <style>{orgTreeCss}</style>

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Struktur Organisasi"
                    description="Hierarki perusahaan, departemen, dan jumlah karyawan aktif. Seret departemen untuk memindahkan posisinya."
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
                            <DragContext.Provider value={dragState}>
                                <div className="org-tree overflow-x-auto pb-4">
                                    <ul>
                                        {tree.map((node) => (
                                            <NodeBox
                                                key={`${node.type}-${node.id}`}
                                                node={node}
                                            />
                                        ))}
                                    </ul>
                                </div>
                            </DragContext.Provider>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

/**
 * Classic CSS org-chart connectors: nested <ul>/<li> laid out horizontally,
 * with pseudo-element lines joining each parent to its children.
 */
const orgTreeCss = `
.org-tree { width: 100%; }
.org-tree ul { display: flex; justify-content: center; padding-top: 24px; position: relative; }
.org-tree li {
    list-style: none;
    position: relative;
    padding: 24px 12px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}
/* connectors from each child up to the horizontal bus */
.org-tree li::before,
.org-tree li::after {
    content: '';
    position: absolute;
    top: 0;
    right: 50%;
    width: 50%;
    height: 24px;
    border-top: 2px solid var(--border);
}
.org-tree li::after {
    right: auto;
    left: 50%;
    border-left: 2px solid var(--border);
}
.org-tree li::before {
    border-right: 2px solid var(--border);
}
/* single child: straight line, no horizontal bus */
.org-tree li:only-child::after,
.org-tree li:only-child::before { display: none; }
.org-tree li:only-child { padding-top: 24px; }
/* trim the outer side of first/last child */
.org-tree li:first-child::before,
.org-tree li:last-child::after { border: 0 none; }
.org-tree li:last-child::before {
    border-right: 2px solid var(--border);
    border-radius: 0 6px 0 0;
}
.org-tree li:first-child::after {
    border-radius: 6px 0 0 0;
}
/* downward stub from a parent into its children bus */
.org-tree ul ul::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    height: 24px;
    border-left: 2px solid var(--border);
}
.org-tree > ul { padding-top: 0; }
.org-tree > ul > li::before,
.org-tree > ul > li::after { display: none; }
.org-node { position: relative; }
`;
