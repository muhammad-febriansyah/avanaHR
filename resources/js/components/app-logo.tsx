export default function AppLogo() {
    return (
        <>
            <img
                src="/assets/logo-full.png"
                alt="AvanaHR"
                className="h-6 w-auto object-contain group-data-[collapsible=icon]:hidden"
            />
            <img
                src="/assets/logo-mark.png"
                alt="AvanaHR"
                className="hidden size-7 shrink-0 object-contain group-data-[collapsible=icon]:block"
            />
        </>
    );
}
