import { Trash2, X } from 'lucide-react';
import {  useState } from 'react';
import type {ReactNode} from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type ConfirmDialogProps = {
    trigger: ReactNode;
    title: string;
    description: string;
    confirmLabel: string;
    onConfirm: () => void;
};

/**
 * Reusable destructive confirmation dialog.
 */
export default function ConfirmDialog({
    trigger,
    title,
    description,
    confirmLabel,
    onConfirm,
}: ConfirmDialogProps) {
    const [open, setOpen] = useState(false);

    function handleConfirm() {
        onConfirm();
        setOpen(false);
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline" type="button">
                            <X />
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        type="button"
                        onClick={handleConfirm}
                    >
                        <Trash2 />
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
