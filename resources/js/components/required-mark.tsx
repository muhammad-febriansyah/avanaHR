/**
 * Red asterisk marking a required form field. See docs/CLAUDE.md §4.
 */
export function RequiredMark() {
    return (
        <span aria-hidden="true" className="text-destructive">
            *
        </span>
    );
}
