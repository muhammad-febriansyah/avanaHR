import { LifeBuoy, Mail, Phone } from 'lucide-react';

const SUPPORT_EMAIL = 'support@avanahr.co.id';
const SUPPORT_PHONE = '(021) 5099-9000';

/**
 * "Butuh Bantuan?" support card in the sidebar footer.
 * Collapses to a single help icon when the sidebar is in icon mode.
 */
export function NavSupport() {
    return (
        <div className="p-2">
            {/* Expanded */}
            <div className="rounded-[10px] bg-muted p-3.5 group-data-[collapsible=icon]:hidden">
                <div className="mb-2.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                    Butuh Bantuan?
                </div>
                <a
                    href={`mailto:${SUPPORT_EMAIL}`}
                    className="mb-2 flex items-center gap-2.5 text-[12.5px] text-foreground transition-colors hover:text-primary"
                >
                    <Mail className="size-4 shrink-0 text-primary" />
                    <span className="truncate">{SUPPORT_EMAIL}</span>
                </a>
                <a
                    href={`tel:+62${SUPPORT_PHONE.replace(/\D/g, '').replace(/^0/, '')}`}
                    className="flex items-center gap-2.5 text-[12.5px] text-foreground transition-colors hover:text-primary"
                >
                    <Phone className="size-4 shrink-0 text-primary" />
                    <span>{SUPPORT_PHONE}</span>
                </a>
            </div>

            {/* Collapsed */}
            <a
                href={`mailto:${SUPPORT_EMAIL}`}
                title="Hubungi support"
                aria-label="Hubungi support"
                className="hidden size-9 items-center justify-center rounded-lg bg-muted text-primary transition-colors hover:bg-accent group-data-[collapsible=icon]:flex"
            >
                <LifeBuoy className="size-[18px]" />
            </a>
        </div>
    );
}
