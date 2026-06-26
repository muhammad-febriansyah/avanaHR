import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

// The global <Toaster> and individual pages both call this hook, so multiple
// components mount it at once. Register a single shared `flash` listener via a
// module-level refcount so the same toast never fires more than once.
let listenerCount = 0;
let unsubscribe: (() => void) | null = null;

export function useFlashToast(): void {
    useEffect(() => {
        listenerCount += 1;

        if (listenerCount === 1) {
            unsubscribe = router.on('flash', (event) => {
                const flash = (event as CustomEvent).detail?.flash;
                const data = flash?.toast as FlashToast | undefined;

                if (!data) {
                    return;
                }

                toast[data.type](data.message);
            });
        }

        return () => {
            listenerCount -= 1;

            if (listenerCount === 0 && unsubscribe) {
                unsubscribe();
                unsubscribe = null;
            }
        };
    }, []);
}
